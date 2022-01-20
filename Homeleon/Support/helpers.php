<?php

use Homeleon\Support\Str;

function d(...$args) {
    vd($args);
}

function dd(...$args) {
    vd($args);
    exit;
}

function vd(...$args) {
    $trace = debug_backtrace()[1];
    $file = explode('\\', $trace['file']);
    $fileLine = end($file) . ':' . $trace['line'];
    if (isCli()) {
        echo "{$fileLine}\n";
    } else {
        echo '<small style="color: green;"><pre>',$fileLine,':</pre></small><pre>';
    }
    call_user_func_array('var_dump', $args);
    echo '</pre>';
}

function isCli() {
    return (php_sapi_name() === 'cli');
}

function isProd() {
    return Config::get('env') === 'prod';
}

function isDev() {
    return Config::get('env') === 'dev';
}

function s($name = null, $value = false) {
    $session = App::make('session');
    if (is_null($name)) {
        return $session->all();
    } elseif ($value === false) {
        return $session->get($name);
    } elseif (is_null($value)) {
        return $session->del($name);
    }

    $session->set($name, $value);

    return $session->get($name);
}

function old($field) {
    return s('_old')[$field] ?? null;
}

function flash($field) {
    return s('_flash')[$field] ?? null;
}

function csrf_token() {
    Session::set('_token', Config::get('csrf_token'));
    return s('_token');
}

function csrf_field() {
    return '<input type="hidden" name="_token" value="'.csrf_token().'">';
}


function generatePasswordHash($password): string {
    return password_hash($password, PASSWORD_BCRYPT);
}

function view(string $view, array $args = []) {
    $view = ROOT . '/' . 'resources/views/' . str_replace('.', '/', $view) . '.php';
    if (!file_exists($view)) {
        throw new Exception("View file `{$view}` not exists.");
    }

    $content  = viewBuffer($view, $args);
    return $content;
}

function viewBuffer($viewPath, $args) {
    global $errors;
    extract($args);
    ob_start();
    include $viewPath;
    $response = ob_get_contents();
    ob_end_clean();

    return $response;
}


function resources($filename) {
    return root() . '/resources/' . $filename;
}

function routes($filename) {
    return root() . '/routes/' . $filename;
}


function root() {
    return ROOT;
}

function redirect($uri = null) {

    $response = App::make('response');

    return $uri ? $response->redirect($uri) : $response->redirect();
}

function route($name, array $params = []) {
    $route = Router::getByName($name);
    $uri = $route ? $route->buildUri($params) : null;
    return $uri;
}

function request() {
    return \App::make('request');
}

function generateToken($userId) {
  $token = Str::random();
  App::make('redis')->set('socket:' . $token, $userId, 10);

  return $token;
}
