<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/helpers.php';
define('ROOT', dirname(dirname(__DIR__)));

use Homeleon\App;
use Homeleon\Socket\{Server, PeriodicEventWorker, Frame};
use App\Server\Application;
use Homeleon\Support\Str;
use Homeleon\Support\Facades\Config;
use Homeleon\Support\Facades\DB;

$core     = new App();
DB::setFetchMode(stdClass::class);


if ($argc > 1 && $argv[1] == '-q') {
    $fp = stream_socket_client("tcp://" . Config::get('host') . ':' . Config::get('port'), $errno, $errstr);
    if (!$fp) {
        echo "Error: $errno - $errstr\n";
    } else {
        fwrite($fp,
        "GET / HTTP/1.1\n" .
        "Sec-WebSocket-Key: ".Str::random(16)."\r\n" .
        "event-key: ".Config::get('key')."\r\n\r\n"
        );
        fwrite($fp, Frame::encode('CLOSE', 'text', true));
    }

    exit;
}


$server = new Server(Config::get('host'), Config::get('port'));
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
