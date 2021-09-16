<?php

namespace App\Repositories;

use App\Application;

abstract class BaseRepository
{
	protected Application $app;

	public function __construct(Application $app)
	{
		$this->app = $app;
	}
}