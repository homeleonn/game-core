<?php

namespace App\Server\Repositories;

use App\Server\Application;

abstract class BaseRepository
{
    public function __construct(
        protected Application $app
    ) {}

    protected function repo($repoName)
    {
        return $this->app->{$repoName};
    }
}
