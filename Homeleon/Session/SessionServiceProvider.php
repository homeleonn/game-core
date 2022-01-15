<?php

namespace Homeleon\Session;

use Redis;
use Homeleon\Support\Facades\Config;
use Exception;
use Homeleon\Support\ServiceProvider;

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
                $redis->connect(
                    $app->config->get('redis')['host'],
                    $app->config->get('redis')['port']
                );
            }

            $sessionHandler = new RedisSessionHandler($redis);
        } else {
            $sessionHandler = new FileSessionHandler();
        }

        return $sessionHandler;
    }
}
