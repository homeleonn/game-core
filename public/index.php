<?php
define('HTTP_SIDE', '');
header("Access-Control-Allow-Origin: *");
require '../vendor/autoload.php';
require '../functions.php';

use Core\App;

try {
    $app = new App();
} catch (Exception $e) {
    // dd($e->getMessage());
}

require routes('web.php');

$app->run();
