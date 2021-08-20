<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../functions.php';

use Core\ServerApp;
use Core\Socket\Server;
use App\Application;

$serverApp = new ServerApp();

$server = new Server(Config::get('host'), Config::get('port'));
$app 	= new Application($server, $redis, $serverApp);

$server->on('start', 	[$app, 'start']);
$server->on('open', 	[$app, 'open']);
$server->on('message', 	[$app, 'message']);
$server->on('close', 	[$app, 'close']);

$server->start();
