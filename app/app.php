<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../functions.php';

use Core\ServerApp;
use Core\Socket\Server;
use Core\Socket\DosProtection;
use App\Application;
use App\Server\Chat\Chat;

$serverApp = new ServerApp();
$dosProtection = new DosProtection(5);
$server = new Server(Config::get('host'), Config::get('port'));
$server->setDosProtection($dosProtection);
// $app 	= new Application($server, $redis, $serverApp);
$app 	= new Chat($server, $redis, $serverApp);

$server->on('start', 	[$app, 'start']);
$server->on('open', 	[$app, 'open']);
$server->on('message', 	[$app, 'message']);
$server->on('close', 	[$app, 'close']);

$server->start();
