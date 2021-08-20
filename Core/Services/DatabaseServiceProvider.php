<?php

namespace Core\Services;

use Core\DB\SafeMySQL;
use Core\Facades\Config;
// use Illuminate\Database\Capsule\Manager as Capsule;

class DatabaseServiceProvider extends AbstractServiceProvider
{
	public function boot()
	{
		// dd(\App\Location::find(1));
		// dd($this->app->make('db')->getConn());
		// $capsule = new Capsule;
		// $capsule->addConnection($this->app->make('db')->getConn());

		// $this->app->set('dbe', function($app) {
		// 	return $capsule;
		// });
	}

	public function register()
	{
		// $capsule = new Capsule;
		// $capsule->addConnection($this->app->config['db_eloquent']);
		// $capsule->bootEloquent();

		// $this->app->set('db', function($app) use ($capsule) {
		// 	return $capsule;
		// });

		$this->app->set('db', function($app) {
			return new SafeMySQL(Config::get('db'));
		});
	}
}