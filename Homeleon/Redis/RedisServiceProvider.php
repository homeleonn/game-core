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
            $redis->connect(
                $app->config->get('redis')['host'],
                $app->config->get('redis')['port']
            );

            return $redis;
        });
    }
}
