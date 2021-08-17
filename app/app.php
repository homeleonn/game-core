<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../functions.php';

use Core\Socket\Server;
use App\Application;

$server = new Server('192.168.0.101', '8080');
$app 	= new Application($server, $redis);

$server->on('start', 	[$app, 'start']);
$server->on('open', 	[$app, 'open']);
$server->on('message', 	[$app, 'message']);
$server->on('close', 	[$app, 'close']);

$server->start();
