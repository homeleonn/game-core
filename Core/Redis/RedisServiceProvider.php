<?php

namespace Core\Redis;

use Core\Support\ServiceProvider;
use Redis;

class RedisServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->set('redis', function ($app) {
            $redis = new Redis;
            $redis->connect('127.0.0.1', 6379);

            return $redis;
        });
    }
}
