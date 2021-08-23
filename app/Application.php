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

	private WebSocketServer $server;
	public StoreContract $store;
	private ServerApp $serverApp;
	public UserRepository $userRepo;
	public LocRepository $locRepo;

	public function __construct(WebSocketServer $server, StoreContract $store, ServerApp $serverApp)
	{
		$this->server 	= $server;
		$this->store 	= $store;
		$this->userRepo = new UserRepository($this);
		$this->locRepo  = new LocRepository($this);
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
		if (!$user = $this->userRepo->init($userId)) return;

		// dd($user);
		
		$this->checkDuplicateConnection($userId);
		$user = $this->userRepo->add($fd, $user);
		$this->locRepo->add($user);

		// users online by loc
		$this->send($fd, ['loc_users' => array_values($this->userRepo->getAllByLoc($user->getLoc()))]);

		$this->getLoc($user);
	}

	// current loc data
	public function getLoc($user)
	{
		if ($loc = $this->locs[$user->getLoc()] ?? null) {
			$this->send($user->getFd(), ['loc' => $loc]);
		}
	}

	public function removeFromApp($fd, $user = null)
	{
		if ($user || $user = $this->userRepo->findByFdAndRemove($fd)) {
			$this->locRepo->remove($user);
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

	public function checkDuplicateConnection($userId)
	{
		if (!$user = $this->userRepo->findById($userId)) return;
		
		$this->disconnectPreviousDuplicateWindow($user->getFd());
		$this->userRepo->remove($user);
		$this->removeFromApp(null, $user);
	}

	public function disconnectPreviousDuplicateWindow($fdId)
	{
		$this->send($fdId, Application::DUPLICATE);
		$this->server->close(null, $fdId);
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
				'from' => $user->getName(),
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
}