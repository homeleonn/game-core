<?php

namespace App\Server\Repositories;

use Homeleon\Support\Facades\DB;
use App\Server\Models\User;
use App\Server\Application;
use Homeleon\Support\Common;

class UserRepository extends BaseRepository
{
    private $storage;
    private $users;
    private $usersFdsById;
    private $marked = []; // Candidates for remove from app

    public function __construct($app)
    {
        parent::__construct($app);
        $this->storage = $app->storage;
    }

    public function add(int $fd, $user)
    {
        $this->users[$fd] = $user;
        $this->usersFdsById[$user->id] = $fd;

        return $this->users[$fd];
    }

    public function findByFd(int $fd): User
    {
        if ($this->has($fd)) {
            return $this->users[$fd];
        }

        $this->app->disconnectUndefinedUser($fd);
        // $this->app->removeFromApp($fd);

        throw new \Exception('User not found');
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
        unset($this->usersFdsById[$user->id]);
        unset($this->users[$user->getFd()]);
    }

    public function init(int $fd, string $userId)
    {
        $user = $this->getUser($userId, $fd);

        $this->checkDuplicateConnection($userId);

        $user = $this->add($fd, $user);
        $this->app->locRepo->addUser($user);

        return $user;
    }

    private function getUser($userId, $fd)
    {
        // $user = DB::getRow('SELECT id, login, level, power, critical, evasion, defence, stamina, last_restore, sex, clan, gold, exp, win, defeat, draw, request, fight, image, title, color, curhp, maxhp, team, loc, trans_time, trans_timeout, super_hits FROM users WHERE id = ?i', $userId);
        $userData = DB::getRow('SELECT u.id, u.login, u.level, u.power, u.critical, u.evasion, u.defence, u.stamina, u.last_restore, u.sex, u.clan, u.gold, u.exp, u.win, u.defeat, u.draw, u.request, u.fight, u.image, u.title, u.color, u.curhp, u.maxhp, u.team, u.loc, u.trans_time, u.trans_timeout, u.super_hits, u.access_level,
            c.name as clan_name, c.img as clan_img,
            t.name as tendency_name, t.img as tendency_img
                FROM users u
                    LEFT JOIN clans c ON u.clan = c.id
                    LEFT JOIN tendencies t ON c.id = t.id
                where u.id = ?i LIMIT 1', $userId);
        $user = new User((array)$userData);
        // $user = User::select('id', 'login', 'level', 'power', 'critical', 'evasion', 'defence', 'stamina', 'last_restore', 'sex', 'clan', 'gold', 'exp', 'win', 'defeat', 'draw', 'request', 'fight', 'image', 'title', 'color', 'curhp', 'maxhp', 'team', 'loc', 'trans_time', 'trans_timeout', 'super_hits')->find($userId);
        $user->setFd($fd);
        // dd($user);

        return $user;
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
        $this->app->send($user->getFd(), ['loc_users' => array_values($this->getAllByLoc($user->loc))]);
    }

    public function getLocUsers($user)
    {
        return array_values($this->getAllByLoc($user->loc));
    }

    public function sendUser($user)
    {
        $this->app->send($user->getFd(), ['me' => $user->getAll()]);
        $this->app->send($user->getFd(), ['time' => time()]);
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
        try {
            $user = $this->findByFd($fd);
            $this->marked[$user->id] = $fd;
            $user->markExit();
        } catch (\Exception $e) {
            print_r($fd);
        }
    }

    public function clearExited()
    {
        $time = time();
        // echo Common::joinBufferLines(function () { print_r($this->getAll()); });
        foreach ($this->marked as $id => $fd) {
            $user = $this->findById($id);
            if ($time > $user->exit) {
                $user->save();
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
        $this->app->locRepo->replaceUserFd($user->loc, $oldFd, $newFd);
        $this->app->locRepo->sendLoc($user);

        return true;
    }
}
