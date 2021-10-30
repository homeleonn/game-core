<?php

namespace Homeleon\Redis;

use Homeleon\Support\ServiceProvider;
use Redis;

class RedisServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->set(Redis::class, function ($app) {
            $redis = new Redis;
            $redis->connect('127.0.0.1', 6379);

            return $redis;
        });
    }
}
