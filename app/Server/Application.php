<?php

namespace App\Server;

use Homeleon\Socket\Server as WebSocketServer;
use Homeleon\Socket\Request;
use Homeleon\Socket\Frame;
use Redis;
use App\Server\Repositories\UserRepository;
use App\Server\Repositories\LocRepository;
use App\Server\Repositories\ItemRepository;
use App\Server\Repositories\FightRepository;
use App\Server\Models\Fighter;
use Homeleon\Support\Facades\Config;
use Homeleon\Support\Facades\DB;

class Application {
    public const DISCONNECT = '0';
    public const DUPLICATE     = '1';

    private $eventManager; // Event manager connection
    private static $instance;

    public WebSocketServer $server;
    public Redis $storage;
    public UserRepository $userRepo;
    public LocRepository $locRepo;
    public ItemRepository $itemRepo;
    public FightRepository $fightRepo;

    public function __construct(WebSocketServer $server, Redis $storage)
    {
        $this->server         = $server;
        $this->storage        = $storage;
        $this->userRepo       = new UserRepository($this);
        $this->locRepo        = new LocRepository($this);
        $this->itemRepo       = new ItemRepository($this);
        $this->fightRepo      = new FightRepository($this);
    }

    public static function getInstance(WebSocketServer $server, Redis $storage)
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
        // file_put_contents(__DIR__ . '/resources/app.pid', getmypid());
    }

    public function open(WebSocketServer $server, Request $request)
    {
        if ($this->setEventManager($request)) return;
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

        if (!$type) return;

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
            DB::query("UPDATE users SET {$queryString} where id = {$payload['userId']}");

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
            $user->itemAction($this, $type, $payload);
        break;

        case 'wearItem':
            $user->itemAction($this, $type, $payload);
        break;

        case 'takeoffItem':
            $user->itemAction($this, $type, $payload);
        break;

        case 'getLocMonsters':
            $this->locRepo->getMonsters($user);
        break;

        case 'getEnemy':
            $this->locRepo->getEnemy($user, $payload);
        break;

        case 'attackMonster':
            $this->locRepo->attackMonster($user, $payload);
        break;

        case 'getFight':
            $this->fightRepo->getById($user);
        break;

        case 'hit':
            $this->fightRepo->hit($user, $payload);
        break;
        }
    }

    public function send($fd, $message)
    {
        if ($fd instanceof Fighter) {
            if (isset($fd->user->aggr) || $fd->isExit()) return;
            $fd = $fd->getFd();
        }
        $this->server->push(
            $fd,
            is_array($message) ? json_encode($message) : $message
        );
    }

    public function close(WebSocketServer $server, int $fd)
    {
        $this->userRepo->markExit($fd);
        // $this->removeFromApp($fd);
    }

    public function addToApp($fd, $userId)
    {
        if ($this->userRepo->restore($fd, $userId)) return;
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
        if ($frame->fd == $this->eventManager) {
            if ($frame->data == 'CLOSE') {
                echo 'i am terminate';
                exit;
            }
        } elseif ($frame->data == 'PING') {
            var_dump(date('H:i:s'), $frame->getPong(), $frame->data);
            $this->send($frame->fd, $frame->getPong());return true;
        }
    }

    public function setEventManager($request)
    {
        if (isset($request->client['event-key']) && $request->client['event-key'] == Config::get('key')) {
            echo "eventManager: $request->fd";
            $this->eventManager = $request->fd;
            return true;
        }

        return false;
    }

    private function messageToLoc($user, $text)
    {
        $this->sendToLoc($user->loc, [
            'message' => [
                'from' => $user->login,
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
            $this->storage->del('socket:' . $token);
            $server->close(null, $request->fd);
            return false;
        }

        $this->storage->del('socket:' . $token);

        return $userId;
    }

    public function periodicEvent($eventName)
    {
        // echo "\n", $eventName, " ", time(), " | ";

        match ($eventName) {
            'clear_exited_users' => $this->userRepo->clearExited(),
            'fight_worker' => $this->fightRepo->cicle(),
            default => null,
        };
    }
}
