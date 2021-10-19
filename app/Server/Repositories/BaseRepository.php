<?php

namespace App\Server\Repositories;

use App\Server\Application;

abstract class BaseRepository
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }
}
