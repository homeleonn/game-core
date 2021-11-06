<?php

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
