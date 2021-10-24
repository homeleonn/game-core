<?php

namespace App\Server\Repositories;

use DB;
use App\Server\Models\User;
use App\Server\Application;
use Core\Support\Common;

class UserRepository
{
    private $storage;
    private $app;
    public $users;
    public $usersFdsById;
    private $marked = []; // Candidates for remove from app

    public function __construct($app)
    {
        $this->app     = $app;
        $this->storage = $app->storage;
    }

    public function add(int $fd, $user)
    {d(new User($this->storage, $fd, $user));
        $this->users[$fd] = new User($this->storage, $fd, $user);
        $this->usersFdsById[$user->id] = $fd;

        return $this->users[$fd];
    }

    public function findByFd(int $fd)
    {
        if ($this->has($fd)) {
            return $this->users[$fd];
        }

        $this->app->disconnectUndefinedUser($fd);
        // $this->app->removeFromApp($fd);
    }

    public function findById(int $id)
    {
        // По айди юзер может быть, но по айди соединения нет
        if (isset($this->usersFdsById[$id]) && isset($this->users[$this->usersFdsById[$id]])) {
            return $this->users[$this->usersFdsById[$id]];
        }
    }

    public function has($fd)
    {
        return isset($this->users[$fd]);
    }

    public function remove($user)
    {
        unset($this->usersFdsById[$user->getId()]);
        unset($this->users[$user->getFd()]);
    }

    public function init(int $fd, string $userId)
    {
        $user = $this->getUser($userId);

        $this->checkDuplicateConnection($userId);

        $user = $this->add($fd, $user);
        $this->app->locRepo->addUser($user);

        return $user;
    }

    private function getUser($userId)
    {
        return DB::getRow('SELECT id, login, level, power, critical, evasion, defence, stamina, last_restore, sex, clan, gold, exp, win, defeat, draw, request, fight, image, title, color, curhp, maxhp, team, loc, trans_time, trans_timeout, super_hits FROM users WHERE id = ?i', $userId);
    }

    public function getAll()
    {
        return $this->users;
    }

    public function getAllByLoc($locId)
    {
        $users = [];

        foreach ($this->app->locRepo->getLoc($locId) as $fd => $dummy) {
            if ($this->has($fd)) {
                $users[] = $this->users[$fd]->locProps();
            }
        }

        return $users;
    }

    public function getIds()
    {
        return $this->usersFdsById;
    }

    public function sendLocUsers($user)
    {
        // users online by location
        $this->app->send($user->getFd(), ['loc_users' => array_values($this->getAllByLoc($user->getLoc()))]);
    }

    public function getLocUsers($user)
    {
        return array_values($this->getAllByLoc($user->getLoc()));
    }

    public function sendUser($user)
    {
        // $this->app->send($user->getFd(), ['me' => (object)[
        //     'id'                 => $user->id,
        //     'login'             => $user->login,
        //     'level'             => $user->level,
        //     'curhp'             => $user->curhp,
        //     'maxhp'             => $user->maxhp,
        //     'loc'                 => $user->loc,
        //     'trans_timeout'     => $user->trans_timeout,
        // ]]);
        // d( $user->getAll());
        $this->app->send($user->getFd(), ['me' => $user->getAll()]);
    }


    public function checkDuplicateConnection($userId)
    {
        if (!$user = $this->findById($userId)) return;

        $this->disconnectPreviousDuplicateConnection($user->getFd());
        $this->remove($user);
        $this->app->removeFromApp(null, $user);
    }

    public function disconnectPreviousDuplicateConnection($fd)
    {
        $this->app->send($fd, Application::DUPLICATE);
        $this->app->server->close(null, $fd);
    }

    public function markExit($fd)
    {
        $user = $this->findByFd($fd);
        $this->marked[$user->id] = $fd;
        $user->markExit();
    }

    public function clearExited()
    {
        $time = time();
        // echo Common::joinBufferLines(function () { print_r($this->getAll()); });
        foreach ($this->marked as $id => $fd) {
            $user = $this->findById($id);
            if ($time > $user->exit) {
                echo "Remove: fd: {$fd}, login: {$user->login} | ";
                $this->app->removeFromApp($fd);
                unset($this->marked[$id]);
            }
        }
    }

    public function restore($newFd, $userId)
    {
        if (!isset($this->marked[$userId])) return false;

        $user = $this->findById($userId);
        $oldFd = $user->getFd();

        $this->users[$newFd] = $user;
        $this->usersFdsById[$user->id] = $newFd;
        unset($this->users[$oldFd]);
        unset($this->marked[$userId]);

        $user->clearMarkExit();
        $user->setFd($newFd);
        $this->sendUser($user);

        return true;
    }
}
