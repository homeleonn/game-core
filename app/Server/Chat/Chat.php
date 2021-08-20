<?php

namespace App\Server\Chat;

use Core\ServerApp;
use Core\Socket\Server;
use Core\Socket\Request;
use Core\Socket\Frame;
use Core\Facades\DB;
use Redis;

class Chat {
	public const DISCONNECT = '0';
	public const DUPLICATE 	= '1';

	private $server;
	private $serverApp;
	private $usersByFd;
	private $usersById;
	private $channels;

	public function __construct(Server $server, Redis $redis, ServerApp $serverApp)
	{
		$this->server 	= $server;
		$this->redis 	= $redis;
	}

	public function start(Server $server)
	{
		cli_set_process_title('FightWorld daemon - php');
		echo "App start on {$server->getIp()}:{$server->getPort()}. PID: ", getmypid(), "\n";
	}

	public function open(Server $server, Request $request)
	{
		if (!$userId = $this->parseToken($server, $request)) return;
		// d($request);
		$this->addToApp($request->fd, $userId);
	}

	public function message(Server $server, Frame $frame) 
	{
		// var_dump($this->usersByFd, $this->usersById, $frame->data, $frame->fd, $this->findUserByFd((int)$frame->fd));
		if (!$user = $this->findUserByFd((int)$frame->fd)) return;

		var_dump(date('H:i:s ') . $frame->data . $user['id']);

		$data = json_decode($frame->data, true);
		// echo $frame->data;
		// print_r($user, array_keys($data)[0]);
		$type = array_keys($data)[0];

		switch ($type) {
		case 'debug':
			$this->send($frame->fd, ['debug' => ['server' => $this]]);
		break;

		case 'message':
			$message = $data[$type];
			$this->messageToRoom($user, $message);
		break;

		case 'subscribe':
			$room = $data[$type];
			$this->subscribe($user, $room);
		break;
		}
	}

	private function subscribe($user, $room)
	{
		$canSubscribeThisChannel = DB::getOne('Select id from users where id = ?i and location = ?i', $user['id'], $room);


		if ($canSubscribeThisChannel) {
			$usersById[$user['id']]['channel'] == $room;
			$usersByFd[$user['fd']]['channel'] == $room;
			$this->channels[$room][] = $user['fd'];
		}
	}

	private function getUserLocationsId($userId)
	{
		return DB::getOne('Select location from users where id = ?i', $userId);
	}

	public function send(int $fd, $message)
	{
		$this->server->push($fd, is_array($message) ? json_encode($message) : $message);
	}

	public function close(Server $server, int $fd)
	{
		$this->removeFromApp($fd);
	}

	public function addToApp($fd, $userId)
	{
		// if (!$user = $this->userRepo->init($userId)) return;
		$this->usersByFd[$fd] = [
			'id' => (int)$userId,
			'fd' => (int)$fd
		];

		$this->usersById[$userId] = [
			'id' => (int)$userId,
			'fd' => (int)$fd
		];
		
		$this->checkDuplicateConnection($userId);

		// users online by room
		// $this->send($fd, ['room_users' => array_values($this->userRepo->getAllByRoom($user->getRoom()))]);
	}

	public function removeFromApp($fd, $user = null)
	{
		// if ($user || $user = $this->userRepo->findByFdAndRemove($fd)) {
		// 	$this->roomRepo->remove($user);
		// }

		$user = $this->usersByFd[$fd];
		unset($this->usersByFd[$fd]);
		unset($this->usersById[$user['id']]);
	}

	public function parseToken(Server $server, Request $request)
	{
		$token  = trim($request->client['request_uri'], '/');

		if (!$userId = $this->redis->get('socket:' . $token)) {
			echo "Invalid token\n";
			$server->close(null, $request->fd);
			return false;
		}

		$this->redis->del('socket:' . $token);

		return $userId;
	}

	public function checkDuplicateConnection($userId)
	{
		if (!$user = $this->findUserById($userId)) return;
		
		$this->disconnectPreviousDuplicateWindow($user['fd']);
		// $this->userRepo->remove($user);
		$this->removeFromApp(null, $user);
	}

	private function findUserByFd($userFd)
	{
		if (isset($this->usersByFd[$userFd])) {
			return $this->usersByFd[$userFd];
		}

		return false;
	}

	private function findUserById($userId)
	{
		if (isset($this->usersById[$userId])) {
			return $this->usersById[$userId];
		}

		return false;
	}

	public function disconnectPreviousDuplicateWindow($fd)
	{
		$this->send($fd, static::DUPLICATE);
		$this->server->close(null, $fd);
	}

	public function disconnectUndefinedUser($userFd)
	{
		// Сессия завершилась, уведомляем и отсоединяем
		$this->send($userFd, ['exit' => false]);
	}

	private function messageToRoom($user, $text)
	{
		if ($channel = $this->getUserLocationsId($user['id'])) {
			$this->sendToRoom($channel, [
				'message' => [
					'from' => $user['id'],
					'to' => null,
					'text' => $text
				]
			]);
		}
	}

	public function sendToRoom($roomId, $message)
	{
		if (!$roomUsersFds = $this->channels[$roomId]) return;

		if (is_array($message)) {
			$message = json_encode($message);
		}

		foreach ($roomUsersFds as $fd => $dummy) {
			$this->server->push($fd, $message);
		}
	}
}