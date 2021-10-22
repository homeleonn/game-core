<?php

define('ROOT', __DIR__);

ini_set('xdebug.var_display_max_depth', 50);
ini_set('xdebug.var_display_max_children', 1024);
ini_set('xdebug.var_display_max_data', 1024);

function d(...$args) {
	vd($args);
}

function dd(...$args) {
	vd($args);
	exit;
}

function vd(){
	$trace = debug_backtrace()[1];
	echo '<small style="color: green;"><pre>',$trace['file'],':',$trace['line'],':</pre></small><pre>';
	// echo $trace['file'], ':', $trace['line'], " -> ";
	call_user_func_array('var_dump', func_get_args());
}

function s($name = null, $value = false) {
    static $start = false;
    if (!$start) {
        global $app;
        $app->storage;
    } else {
        $start = true;
    }

	if (is_null($name)) {
		return $_SESSION;
	} elseif ($value === false) {
		return $_SESSION[$name] ?? null;
	} elseif (is_null($value)) {
		unset($_SESSION[$name]);
		return;
	}

	$_SESSION[$name] = (string)$value;

	return $_SESSION[$name];
}


function generatePasswordHash($password): string {
	return password_hash($password, PASSWORD_BCRYPT);
}

function authAttempt($password): bool {
	return password_verify($password, generatePasswordHash($password));
}

function generateRandomString($length = 20) {
	return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 3)), 0, $length);
}

function view(string $view, array $args = []) {
	$view = ROOT . '/' . 'resources/views/' . $view . '.php';
	if (!file_exists($view)) {
		throw new Exception("View file `{$view}` not exists.");
	}

    $content  = viewBuffer($view, $args);
    return $content;
    // return viewBuffer('resources/views/layouts/main.php', ['content' => $content]);
}

function viewBuffer($viewPath, $args) {
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

function route($name) {
	$route = Router::getByName($name);
	// dd($route);
	return $route ? $route->getUri() : null;
}

function request() {
	return \App::make('request');
}

function generateToken($userId) {
  $token = generateRandomString();
  App::make('storage')->set('socket:' . $token, $userId, 10);

  return $token;
}
