<?php

namespace App;

use Core\ServerApp;
use Core\Socket\Server;
use Core\Socket\Request;
use Core\Socket\Frame;
use Core\Facades\DB;
use App\Server\Contracts\StoreContract;

class Application {
	public const DISCONNECT = '0';
	public const DUPLICATE 	= '1';

	private $eventManager; // Event manager connection

	private $server;
	private $store;
	private $serverApp;
	public $userRepo;
	public $locRepo;
	public $locs = [];
	public $locsAccess = [];

	public function __construct(Server $server, StoreContract $store, ServerApp $serverApp)
	{
		$this->server 	= $server;
		$this->store 	= $store;
		$this->userRepo = new UserRepository($store, $this);
		$this->locRepo = new LocRepository($this);
		$this->loadLocs();
		// print_r($this->locs);
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
			$this->messageToLoc($user, $data[$type]);
		break;

		case 'chloc':
			$user->chloc((int)$data[$type], $this);
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

	private function loadLocs()
	{
		$locsBuff = DB::getAll('Select * from locs');
		$locsAccess = DB::getAll('Select * from locs_access');

		// $locsBuff = \App\Client\Models\Location::all();
		// $locsAccess = \App\Client\Models\LocationAccess::all();


		// collect array access locs by id
		foreach ($locsAccess as $access) {
			if (!isset($this->locsAccess[$access->loc_id])) $this->locsAccess[$access->loc_id] = [];
			$this->locsAccess[$access->loc_id][] = $access->access_loc_id;
		}

		// locs by id
		foreach ($locsBuff as $loc) {
			$this->locs[$loc->id] = $loc;
			$loc->locs_coords = json_decode($loc->locs_coords);
		}

		// Bind closest locs and sort them by id
		foreach ($this->locs as $id => $loc) {
			// $this->locs[$id]->closest_locs = $this->locsAccess[$id];

			foreach ($this->locsAccess[$id] as $locId) {
				if (!isset($this->locs[$id]->closest_locs[$this->locs[$locId]->type])) {
					$this->locs[$id]->closest_locs[$this->locs[$locId]->type] = [];
				}

				$this->locs[$id]->closest_locs[$this->locs[$locId]->type][$this->locs[$locId]->id] = $this->locs[$locId]->name;
			}
			
		}

		// print_r();
	}

	public function removeFromApp($fd, $user = null)
	{
		if ($user || $user = $this->userRepo->findByFdAndRemove($fd)) {
			$this->locRepo->remove($user);
		}
	}

	public function parseToken(Server $server, Request $request)
	{
		$token  = trim($request->client['request_uri'], '/');

		if (!$userSID = $this->store->get('socket:' . $token)) {
			echo "Invalid token\n";
			$server->close(null, $request->fd);
			return false;
		}

		$this->store->del('socket:' . $token);

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