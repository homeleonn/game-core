<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../functions.php';

use Core\App;
use Core\Socket\Server;
use App\Server\Application;

$core 	= new App();
$server = new Server(Config::get('host'), Config::get('port'));
$server->setDosProtection($core->make('dosprotection'));
$app 		= new Application($server, $core->make('storage'));

$server->on('start', 	[$app, 'start']);
$server->on('open', 	[$app, 'open']);
$server->on('message', 	[$app, 'message']);
$server->on('close', 	[$app, 'close']);

$server->start();
