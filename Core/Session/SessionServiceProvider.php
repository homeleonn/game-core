<?php

namespace Core\Session;

use Redis;
use Config;
use Exception;
use Core\Support\ServiceProvider;

class SessionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $config = Config::get('session');

        if ($config['driver'] === 'redis') {
            try {
                $redis = $this->app->make('redis');
            } catch (Exception $e) {
                $redis = new Redis;
                $redis->connect('127.0.0.1', '6379');
            }

            $savePath = 'tcp://127.0.0.1:6379';
            $sessionHandler = new RedisSessionHandler($redis);
        } else {
            $savePath = $config['path'];
            $sessionHandler = new FileSessionHandler();

        }

        ini_set('session.save_path', $savePath);
        ini_set('session.serialize_handler', 'php_serialize');
        session_set_save_handler($sessionHandler, true);
        session_start();

        $this->app->set('storage', function ($app) use ($sessionHandler) {
            return new Storage($sessionHandler);
        });
    }
}
