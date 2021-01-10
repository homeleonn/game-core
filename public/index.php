<?php

require '../vendor/autoload.php';
require '../../functions.php';

use App\Core\App;


$app = new App();

$app->router->get('/', function() {
   return 'Hello world';
});

$app->router->get('/contact', function() {
    return 'Contact page';
});

$app->router->post('/post', function() {
    return 'Hello world from post request';
});

$app->run();