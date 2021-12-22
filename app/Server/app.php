<?php
ini_set('date.timezone', 'Europe/Kiev');

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/helpers.php';
define('ROOT', dirname(dirname(__DIR__)));

use Homeleon\App;
use Homeleon\Socket\{Server, PeriodicEventWorker};
use App\Server\Application;
use Homeleon\Support\Facades\Config;
use Homeleon\Support\Facades\DB;

$core = new App();

checkAppTerminate();

DB::setFetchMode(stdClass::class);
$server = new Server(
    Config::get('host'),
    Config::get('port'),
    Config::get('ssl') ? __DIR__ . 'resources/ssl/cert.pem' : null
);
$app    = new Application($server, $core->make('redis'));
$core->set('game', $app);

$server->setDosProtection($core->make('dosprotection'));

$pe = new PeriodicEventWorker($app);
$pe->addEvent('clear_exited_users', 60);
$pe->addEvent('fight_worker', 3);
$pe->addEvent('respawn_npc', 10);
$pe->addEvent('ping', 50);

$server->setPeriodicEventWorker($pe);

$server->on('start',     [$app, 'start']);
$server->on('open',     [$app, 'open']);
$server->on('message',     [$app, 'message']);
$server->on('close',     [$app, 'close']);

$server->start();
