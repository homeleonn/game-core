<?php

use Homeleon\Socket\Frame;
use Homeleon\Support\Str;
use Homeleon\Support\Facades\Config;

/**
 * returns app repo
 */
function repo($repo) {
    static $app;

    if (!$app) {
        $app = App::make('game');
    }

    return $app->{$repo . 'Repo'};
}

function error($fd, $message = 'Возникла неизвестная ошибка.') {
    global $app;
    $app->send($fd, ['error' => $message]);
}

function send($fd, $message) {
    global $app;
    $app->send($fd, $message);
}


function isDrop($chance) {
    return mt_rand(0, 1000) < $chance;
}

function inc(&$arr, $index, $count) {
    return $arr[$index] = ($arr[$index] ?? 0) + $count;
}
function dec(&$arr, $index, $count) {
    return $arr[$index] = ($arr[$index] ?? 0) - $count;
}

function checkAppTerminate($argc, $argv) {
//    global $app;
    // $app->log(implode(', ', $argv));
    var_dump($argv);

    if ($argc > 1 && $argv[1] == '-q') {
        $fp = stream_socket_client("tcp://" . Config::get('host') . ':' . Config::get('port'), $errno, $errstr);
        if (!$fp) {
            echo "Error: $errno - $errstr\n";
        } else {
            // echo "$fp\n";
            fwrite($fp,
            "GET /123 HTTP/1.1\n" .
            "Sec-WebSocket-Key: ".Str::random(16)."\r\n" .
            "event-key: ".Config::get('app_key')."\r\n\r\n"
            );
            fwrite($fp, Frame::encode('CLOSE', 'text', true));
        }

        exit;
    }
}



$logFile = __DIR__ . '/resources/log/error.log';
// file_put_contents($logFile, '');
function myErrorHandler($errno, $errstr, $errfile, $errline)
{
    global $logFile;
    $trace = '';
    foreach (debug_backtrace() as $i => $debug) {
        if (!$i || $i > 4) continue;
        $trace .= "{$debug['file']}:{$debug['line']}\n";
    }
    file_put_contents($logFile, '[' . date('d-m-Y H:i:s') . '] ' . $errno . ' | ' . $errstr . ' in ' . $errfile . ':' . $errline. "\n{$trace}\n\n");

    return false;
}
set_error_handler("myErrorHandler");

function _log(string $string)
{
    global $logFile;
    file_put_contents($logFile, '[' . date('d-m-Y H:i:s') . '] ' . $string);
}