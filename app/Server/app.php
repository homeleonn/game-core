<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../functions.php';

use Core\App;
use Core\Socket\{Server, PeriodicEventWorker};
use App\Server\Application;

$core 	= new App();
$server = new Server(Config::get('host'), Config::get('port'));
$app 		= new Application($server, $core->make('storage'));
$core->set('game', $app);

$server->setDosProtection($core->make('dosprotection'));

$pe = new PeriodicEventWorker($app);
$pe->addEvent('clear_exited_users', 60);
$pe->addEvent('fight_worker', 3);

$server->setPeriodicEventWorker($pe);

$server->on('start', 	[$app, 'start']);
$server->on('open', 	[$app, 'open']);
$server->on('message', 	[$app, 'message']);
$server->on('close', 	[$app, 'close']);

$server->start();
