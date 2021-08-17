<?php

require '../vendor/autoload.php';
require '../functions.php';

use Core\Session\Init as SessionInit;

new SessionInit($redis);

use Core\App;

try {
	$app = new App();
} catch (Exception $e) {
	// dd($e->getMessage());
}

require routes('web.php');

$app->run();