<?php

namespace App;

use Core\ServerApp;
use Core\Socket\Server;
use Core\Socket\Request;
use Core\Socket\Frame;
use Core\Facades\DB;
use Redis;

class Application {
	public const DISCONNECT = '0';
	public const DUPLICATE 	= '1';

	private $eventManager; // Event manager connection

	private $server;
	private $redis;
	private $serverApp;
	public $userRepo;
	public $roomRepo;
	public $locations = [];
	public $locationsAccess = [];

	public function __construct(Server $server, Redis $redis, ServerApp $serverApp)
	{
		$this->server 	= $server;
		$this->redis 	= $redis;
		// $this->serverApp = $serverApp;
		// print_r(\DB::getAll('Select * from users'));
		$this->userRepo = new UserRepository($redis, $this);
		$this->roomRepo = new RoomRepository($this);
		$this->loadLocations();
		print_r($this->locations);
		// $this->locations = json_decode(file_get_contents(__DIR__.'/../resources/location.json'), true);
	}

	public function start(Server $server)
	{
		cli_set_process_title('FightWorld daemon - php');
		echo "App start on {$server->getIp()}:{$server->getPort()}. PID: ", getmypid(), "\n";
	}

	public function open(Server $server, Request $request)
	{
		if (!$userSID = $this->parseToken($server, $request)) return;

		$this->addToApp($request->fd, $userSID);
	}

	public function message(Server $server, Frame $frame) 
	{
		if ($this->isAppManagersMessage($frame)) return;
		if (!$user = $this->userRepo->findByFd($frame->fd)) return;

		var_dump(date('H:i:s ') . $frame->data . $user);

		$data = json_decode($frame->data, true);
		$type = array_keys($data)[0];

		switch ($type) {
		case 'debug':
			$this->send($frame->fd, ['debug' => ['server' => $this]]);
		break;

		case 'message':
			$this->messageToRoom($user, $data[$type]);
		break;

		case 'chroom':
			$user->chroom((int)$data[$type], $this);
		break;
		}
	}

	public function send(int $fd, $message)
	{
		$this->server->push($fd, is_array($message) ? json_encode($message) : $message);
	}

	public function close(Server $server, int $fd)
	{
		$this->removeFromApp($fd);
	}

	public function addToApp($fd, $userSID)
	{
		if (!$user = $this->userRepo->init($userSID)) return;
		
		$this->checkDuplicateConnection($user['id']);
		$user = $this->userRepo->add($fd, $userSID, $user);
		$this->roomRepo->add($user);

		// users online by room
		$this->send($fd, ['room_users' => array_values($this->userRepo->getAllByRoom($user->getRoom()))]);

		$this->getLocation($user);
	}

	// current location data
	public function getLocation($user)
	{
		if ($location = $this->locations[$user->getRoom()] ?? null) {
			$this->send($user->getFd(), ['location' => $location]);
		}
	}

	private function loadLocations()
	{
		$locationsBuff = DB::getAll('Select * from locations');
		$locationsAccess = DB::getAll('Select * from locations_access');

		// collect array access locations by id
		foreach ($locationsAccess as $access) {
			if (!isset($this->locationsAccess[$access->loc_id])) $this->locationsAccess[$access->loc_id] = [];
			$this->locationsAccess[$access->loc_id][] = $access->access_loc_id;
		}

		// locations by id
		foreach ($locationsBuff as $location) {
			$this->locations[$location->id] = $location;
			$location->locations_coords = json_decode($location->locations_coords);
		}

		// Bind closest locations and sort them by id
		foreach ($this->locations as $id => $location) {
			// $this->locations[$id]->closest_locations = $this->locationsAccess[$id];

			foreach ($this->locationsAccess[$id] as $locationId) {
				if (!isset($this->locations[$id]->closest_locations[$this->locations[$locationId]->type])) {
					$this->locations[$id]->closest_locations[$this->locations[$locationId]->type] = [];
				}

				$this->locations[$id]->closest_locations[$this->locations[$locationId]->type][$this->locations[$locationId]->id] = $this->locations[$locationId]->name;
			}
			
		}

		// print_r();
	}

	public function removeFromApp($fd, $user = null)
	{
		if ($user || $user = $this->userRepo->findByFdAndRemove($fd)) {
			$this->roomRepo->remove($user);
		}
	}

	public function parseToken(Server $server, Request $request)
	{
		$token  = trim($request->server['request_uri'], '/');

		if (!$userSID = $this->redis->get('socket:' . $token)) {
			echo "Invalid token\n";
			$server->close(null, $request->fd);
			return false;
		}

		$this->redis->del('socket:' . $token);

		return $userSID;
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

	private function messageToRoom($user, $text)
	{
		$this->sendToRoom($user->getRoom(), [
			'message' => [
				'from' => $user->getName(),
				'to' => null,
				'text' => $text
			]
		]);
	}

	public function sendToRoom($roomId, $message)
	{
		if (!$roomUsersFds = $this->roomRepo->getRoom($roomId)) return;

		if (is_array($message)) {
			$message = json_encode($message);
		}

		foreach ($roomUsersFds as $fd => $dummy) {
			$this->server->push($fd, $message);
		}
	}
}