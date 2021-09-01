<?php

namespace App;

use Core\ServerApp;
use Core\Socket\Server as WebSocketServer;
use Core\Socket\Request;
use Core\Socket\Frame;
use App\Server\Contracts\StoreContract;

class Application {
	public const DISCONNECT = '0';
	public const DUPLICATE 	= '1';

	private $eventManager; // Event manager connection

	public WebSocketServer $server;
	public StoreContract $store;
	public UserRepository $userRepo;
	public LocRepository $locRepo;
	public ItemRepository $itemRepo;

	public function __construct(WebSocketServer $server, StoreContract $store)
	{
		$this->server 	= $server;
		$this->store 	= $store;
		$this->userRepo = new UserRepository($this);
		$this->locRepo  = new LocRepository($this);
		$this->itemRepo = new ItemRepository($this);
	}

	public function start(WebSocketServer $server)
	{
		cli_set_process_title('FightWorld daemon - php');
		echo "App start on {$server->getIp()}:{$server->getPort()}. PID: ", getmypid(), "\n";
	}

	public function open(WebSocketServer $server, Request $request)
	{
		if (!$userId = $this->parseToken($server, $request)) return;

		$this->addToApp($request->fd, $userId);
	}

	public function message(WebSocketServer $server, Frame $frame) 
	{
		// if ($this->isAppManagersMessage($frame)) return;
		if (!$user = $this->userRepo->findByFd($frame->fd)) return;

		var_dump(date('H:i:s ') . $frame->data . $user);

		$data = json_decode($frame->data, true);
		$type = array_keys($data)[0];

		switch ($type) {
		case 'debug':
			$this->send($frame->fd, ['debug' => ['server' => $this]]);
		break;

		case 'message':
			$this->messageToLoc($user, $data[$type]);
		break;

		case 'chloc':
			$user->chloc((int)$data[$type], $this);
		break;

		case 'getBackPack':
			$user->getBackPack($this);
		break;

		case 'removeItem':
			$user->itemAction($this, 'removeItem', $data[$type]);
		break;

		case 'wearItem':
			$user->itemAction($this, 'wearItem', $data[$type]);
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
		// Сессия завершилась, уведомляем и отсоединяем
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

		if (!$userId = $this->store->get('socket:' . $token)) {
			echo "Invalid token\n";
			$server->close(null, $request->fd);
			return false;
		}

		$this->store->del('socket:' . $token);

		return $userId;
	}
}