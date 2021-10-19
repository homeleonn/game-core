<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../functions.php';

use Core\App;
use Core\Socket\{Server, PeriodicEventWorker, Frame};
use App\Server\Application;
$core     = new App();

// echo generateRandomString(32);exit;

// var_dump(Config::get('host'));

if ($argc > 1 && $argv[1] == '-q') {
    $fp = stream_socket_client("tcp://" . Config::get('host') . ':' . Config::get('port'), $errno, $errstr);
    if (!$fp) {
        echo "Error: $errno - $errstr\n";
    } else {
        fwrite($fp,
        "GET / HTTP/1.1\n" .
        "Sec-WebSocket-Key: ".generateRandomString(16)."\r\n" .
        "event-key: ".Config::get('key')."\r\n\r\n"
        );
        fwrite($fp, Frame::encode('CLOSE', 'text', true));
    }

    exit;
}

$server = new Server(Config::get('host'), Config::get('port'));
$app         = new Application($server, $core->make('storage'));
$core->set('game', $app);

$server->setDosProtection($core->make('dosprotection'));

$pe = new PeriodicEventWorker($app);
$pe->addEvent('clear_exited_users', 60);
$pe->addEvent('fight_worker', 3);

$server->setPeriodicEventWorker($pe);

$server->on('start',     [$app, 'start']);
$server->on('open',     [$app, 'open']);
$server->on('message',     [$app, 'message']);
$server->on('close',     [$app, 'close']);

$server->start();
