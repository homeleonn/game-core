<?php

namespace Core\Session;

use Redis;
use Core\Support\Facades\Config;
use Exception;
use Core\Support\ServiceProvider;

class SessionServiceProvider extends ServiceProvider
{
    private string $sessionName = 'fw_session';

    public function boot()
    {
        $this->app->make(Session::class);
    }

    public function register()
    {
        $sessionHandler = $this->setSessionHandler();
        $this->app->set(Session::class, function ($app) use ($sessionHandler) {
            return new Session($sessionHandler);
        });
    }

    private function setSessionHandler()
    {
        $config = $this->app->make('config')->get('session');

        if ($config['driver'] === 'redis') {
            try {
                $redis = $this->app->make(Redis::class);
            } catch (Exception $e) {
                $redis = new Redis;
                $redis->connect('127.0.0.1', 6379);
            }

            $sessionHandler = new RedisSessionHandler($redis);
        } else {
            $sessionHandler = new FileSessionHandler();
        }

        return $sessionHandler;
    }
}
