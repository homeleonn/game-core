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
