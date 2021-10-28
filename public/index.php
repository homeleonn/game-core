<?php
// var_dump($_SERVER);exit;

define('HTTP_SIDE', '');
header("Access-Control-Allow-Origin: *");
require '../vendor/autoload.php';
// require '../functions.php';
// require '../Core/Support/helpers.php';

use Core\App;

try {
    $app = new App();
} catch (Exception $e) {
    echo $e->getMessage();
    exit;
    // d($e);
}

require routes('web.php');

$app->run();
d($app->db->getStats());
