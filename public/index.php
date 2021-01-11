<?php

require '../vendor/autoload.php';
require '../../functions.php';
require '../helpers.php';

use App\Core\App;
use App\Controllers\HomeController;


$app = new App();

$app->router->get('', [HomeController::class, 'index']);
$app->router->get('contact', [HomeController::class, 'contact']);
$app->router->get('contact1', function() {
    return 'Contact page';
});
$app->router->post('post', function() {
    return 'Hello world from post request';
});

$app->run();