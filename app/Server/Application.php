<?php

namespace App\Server;

use Core\Socket\Server as WebSocketServer;
use Core\Socket\Request;
use Core\Socket\Frame;
use Core\Contracts\Session\Session;
use App\Server\Repositories\UserRepository;
use App\Server\Repositories\LocRepository;
use App\Server\Repositories\ItemRepository;

class Application {
	public const DISCONNECT = '0';
	public const DUPLICATE 	= '1';

	private $eventManager; // Event manager connection
	private static $instance;

	public WebSocketServer $server;
	public Session $storage;
	public UserRepository $userRepo;
	public LocRepository $locRepo;
	public ItemRepository $itemRepo;

	public function __construct(WebSocketServer $server, Session $storage)
	{
		$this->server 	= $server;
		$this->storage 		= $storage;
		$this->userRepo = new UserRepository($this);
		$this->locRepo  = new LocRepository($this);
		$this->itemRepo = new ItemRepository($this);
	}

	public static function getInstance(WebSocketServer $server, Session $storage)
	{
		if (!static::$instance) {
			static::$instance = new static($server, $storage);
		}

		return static::$instance;
	}

	public function start(WebSocketServer $server)
	{
		cli_set_process_title('FightWorld daemon - php');
		system('echo "\033]0;"FightWorld"\007"');
		echo "App start on {$server->getIp()}:{$server->getPort()}. PID: ", getmypid(), "\n";
	}

	public function open(WebSocketServer $server, Request $request)
	{
		if (!$userId = $this->parseToken($server, $request)) return;

		$this->addToApp($request->fd, $userId);
	}

	public function message(WebSocketServer $server, Frame $frame) 
	{
		if ($this->isAppManagersMessage($frame)) return;
		if (!$user = $this->userRepo->findByFd($frame->fd)) return;

		var_dump(date('H:i:s ') . $frame->data . $user);

		$data = json_decode($frame->data, true);
		$type = array_keys($data)[0];
		$payload = $data[$type];

		switch ($type) {
		case 'debug':
			$this->send($frame->fd, ['debug' => ['server' => $this]]);
		break;

		case 'admin_user':
			// if (!$user->isAdmin()) return;

			if (!$user = $this->userRepo->findById($payload['userId'])) return;

			$queryString = '';
			foreach ($payload['props'] as $prop => $value) {
				$user->{$prop} = $value;
				$queryString .= "{$prop}={$value},";
			}

			$queryString = substr($queryString, 0, -1);
			\DB::query("UPDATE users SET {$queryString} where id = {$payload['userId']}");

			$this->send($user->getFd(), ['me' => $user->getAll()]);
		break;

		case 'sendMessage':
			$this->messageToLoc($user, $payload);
		break;

		case 'chloc':
			$user->chloc((int)$payload, $this);
		break;

		case 'getBackPack':
			$user->getBackPack($this);
		break;

		case 'removeItem':
			$user->itemAction($this, 'removeItem', $payload);
		break;

		case 'wearItem':
			$user->itemAction($this, 'wearItem', $payload);
		break;

		case 'getLocMonsters':
			$this->locRepo->getMonsters($user);
		break;

		case 'getEnemy':
			$this->locRepo->getEnemy($user, $payload);
		break;
		}
	}

	public function send(int $fd, $message)
	{
		$this->server->push(
			$fd, 
			is_array($message) ? json_encode($message) : $message
		);
	}

	public function close(WebSocketServer $server, int $fd)
	{
		$this->removeFromApp($fd);
	}

	public function addToApp($fd, $userId)
	{
		if (!$user = $this->userRepo->init($fd, $userId)) return;

		$this->userRepo->sendUser($user);
		$this->locRepo->sendLoc($user);
	}

	public function removeFromApp($fd, $user = null)
	{
		if ($user || $user = $this->userRepo->findByFd($fd)) {
			$this->userRepo->remove($user);
			$this->locRepo->removeUser($user);
		}
	}

	public function disconnectUndefinedUser($userFd)
	{
		// The session has finished, notice and disconnect
		$this->send($userFd, ['exit' => false]);
	}

	public function isAppManagersMessage(Frame $frame)
	{
		if ($frame->data == 'PING') {
			var_dump(date('H:i:s'), $frame->getPong(), $frame->data);
			$this->send($frame->fd, $frame->getPong());return true;
		}
	}

	private function messageToLoc($user, $text)
	{
		$this->sendToLoc($user->getLoc(), [
			'message' => [
				'from' => $user->getLogin(),
				'to' => null,
				'text' => $text
			]
		]);
	}

	public function sendToLoc($locId, $message)
	{
		if (!$locUsersFds = $this->locRepo->getLoc($locId)) return;

		if (is_array($message)) {
			$message = json_encode($message);
		}

		foreach ($locUsersFds as $fd => $dummy) {
			$this->server->push($fd, $message);
		}
	}

	public function parseToken(WebSocketServer $server, Request $request)
	{
		$token  = trim($request->client['request_uri'], '/');

		if (!$userId = $this->storage->get('socket:' . $token)) {
			echo "Invalid token\n";
			$server->close(null, $request->fd);
			return false;
		}

		$this->storage->del('socket:' . $token);

		return $userId;
	}
}