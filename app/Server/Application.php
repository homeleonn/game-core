<?php

namespace App\Server;

use Redis;
use Homeleon\Socket\{
    Server as WebSocketServer,
    Request,
    Frame,
};
use App\Server\Repositories\{
    UserRepository,
    LocRepository,
    ItemRepository,
    FightRepository,
    NpcRepository,
    QuestRepository,
    DropRepository,
};
use Homeleon\Support\Facades\Config;
use Homeleon\Support\Facades\DB;
use Homeleon\Support\OS;
use App\Server\Models\Fighter;

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
    public QuestRepository $questRepo;
    public DropRepository $dropRepo;
    public NpcRepository $npcRepo;

    public function __construct(WebSocketServer $server, Redis $storage)
    {
        $this->server         = $server;
        $this->storage        = $storage;
        $this->userRepo       = new UserRepository($this);
        $this->locRepo        = new LocRepository($this);
        $this->itemRepo       = new ItemRepository($this);
        $this->fightRepo      = new FightRepository($this);
        $this->npcRepo        = new NpcRepository($this);
        $this->questRepo      = new QuestRepository($this);
        $this->dropRepo       = new DropRepository($this);
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
        if (!OS::isWin()) system('echo "\033]0;"FightWorld"\007"');
        $this->log("App start on {$server->getIp()}:{$server->getPort()}. PID: ", getmypid());
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

        $data = $this->fetchDataFromFrame($frame->data);
        $type = $this->fetchDataTypeFromFrame($data);

        if (!$type) return;

        $payload = $data[$type];

        match ($type) {
            'debug'         => $this->send($frame->fd, ['debug' => ['server' => $this]]),
            'admin_user'    => $this->adminTestActions($payload, $user),
            'sendMessage'   => $this->messageToLoc($user, $payload),
            'chloc'         => $user->chloc((int)$payload, $this),
            'getBackPack'   => $user->getBackPack($this),
            'removeItem'    => $user->itemAction($this, $type, $payload),
            'wearItem'      => $user->itemAction($this, $type, $payload),
            'takeoffItem'   => $user->itemAction($this, $type, $payload),
            'getLocMonsters' => $this->locRepo->getMonsters($user),
            'getEnemy'      => $this->locRepo->getEnemy($user, $payload),
            'attackMonster' => $this->locRepo->attackMonster($user, $payload),
            'attackUser'    => $this->locRepo->attackUser($user, $payload),
            'getFight'      => $this->fightRepo->getById($user),
            'hit'           => $this->fightRepo->hit($user, $payload),
            'talkToNpc'     => $this->questRepo->talkToNpc($user, $payload),
            'showQuest'     => $this->questRepo->showQuest($user, ...$payload),
            'questAnswer'   => $this->questRepo->answer($user, ...$payload),
            'takeReward'    => $this->questRepo->takeReward($user, ...$payload),
            'getQuests'     => $user->getQuests(),
            default => null,
        };

    }

    public function fetchDataFromFrame($frameData, $user = null)
    {
        $this->log($frameData, $user);
        return json_decode($frameData, true);
    }

    public function fetchDataTypeFromFrame($data)
    {
        return array_keys($data)[0];
    }


    public function send($fd, $message, $type = 'text')
    {
        if ($fd instanceof Fighter) {
            if (isset($fd->user->aggr) || $fd->isExit()) return;
            $fd = $fd->getFd();
        }
        $this->server->push(
            $fd,
            is_array($message) ? json_encode($message) : $message,
            $type
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
        try {
            if ($user || $user = $this->userRepo->findByFd($fd)) {
                $this->userRepo->remove($user);
                $this->locRepo->removeUser($user);
            }
        } catch (\Exception $e) {}
    }

    public function disconnectUndefinedUser($userFd)
    {
        // The session has finished, notice and disconnect
        $this->send($userFd, ['exit' => false]);
    }

    public function isAppManagersMessage(Frame $frame)
    {
        // d($frame);
        if ($frame->fd == $this->eventManager) {
            if ($frame->data == 'CLOSE') {
                $this->log('i am terminate');
                exit;
            }
        } elseif ($frame->data == 'PING') {
            $this->log($frame->getPong(), $frame->data);
            $this->send($frame->fd, $frame->getPong());return true;
        } elseif ($frame->type == 'pong') {
            return true;
        }
    }

    public function setEventManager($request): bool
    {
        if (isset($request->client['event-key']) && $request->client['event-key'] == Config::get('app_key')) {
            $this->log("eventManager: $request->fd");
            $this->eventManager = $request->fd;
            exit;
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
                'text' => htmlspecialchars(strip_tags($text))
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
            $this->log("Invalid token");
            $this->storage->del('socket:' . $token);
            $server->close(null, $request->fd);
            return false;
        }

        $this->storage->del('socket:' . $token);

        return $userId;
    }

    public function pingToAll()
    {
        $users = $this->userRepo->getAll();

        if (empty($users)) {
            return;
        }

        foreach ($users as $user) {
            $this->send($user->getFd(), 'ping', 'ping');
        }
    }

    public function adminTestActions($payload, $user)
    {
        if (!$user->isAdmin()) return;

        if (!$user = $this->userRepo->findById($payload['userId'])) return;

            $queryString = '';
            foreach ($payload['props'] as $prop => $value) {
                if ($value == 'now()') $value = time();
                $user->{$prop} = $value;
                $queryString .= "{$prop}={$value},";
            }

            $queryString = substr($queryString, 0, -1);
            DB::query("UPDATE users SET {$queryString} where id = {$payload['userId']}");

            $this->send($user->getFd(), ['me' => $user->getAll()]);
    }

    public function periodicEvent($eventName):void
    {
//        $this->log($eventName);

        match ($eventName) {
            'clear_exited_users' => $this->userRepo->clearExited(),
            'fight_worker' => $this->fightRepo->cycle(),
            'respawn_npc' => $this->npcRepo->respawn(),
            'client_ping' => $this->pingToAll(),
            'db_ping' => DB::ping(),
            default => null,
        };
    }

    public function log(...$message): void
    {
        echo date('Y-m-d H:i:s '), join(' ', $message), "\n";
    }
}
