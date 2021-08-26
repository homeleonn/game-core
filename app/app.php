<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../functions.php';

use Core\App;
use Core\Socket\Server;
use Core\Socket\DosProtection;
use App\Application;
use App\Server\Chat\Chat;
use App\Server\Contracts\StoreContract;
use App\Server\Store\RedisStore as Store;

$serverApp = new App();
$dosProtection = new DosProtection(50);
$server = new Server(Config::get('host'), Config::get('port'));
$server->setDosProtection($dosProtection);
$store = new Store($redis);
$app 	= new Application($server, $store, $serverApp);

$server->on('start', 	[$app, 'start']);
$server->on('open', 	[$app, 'open']);
$server->on('message', 	[$app, 'message']);
$server->on('close', 	[$app, 'close']);

$server->start();
