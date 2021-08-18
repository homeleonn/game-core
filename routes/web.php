<?php

use Core\Facades\Route;
use App\Controllers\HomeController;
use App\Middleware\AuthMiddleware;

Route::group(['middleware' => [AuthMiddleware::class]], function() {
	Route::get('main', [HomeController::class, 'main'])->name('main');
	Route::post('logout', [HomeController::class, 'logout'])->name('logout');
	Route::get('wsToken', [HomeController::class, 'wsToken']);
});

Route::group(['middleware' => 'guest'], function() {
	Route::get('', [HomeController::class, 'entry'])->name('entry');
	// Route::get('', function() {
	// 	dd(\DB::getAll('Select * from users'));
	// });
	Route::post('login', [HomeController::class, 'login'])->name('login');
});

Route::get('post', function() {
    return 'Hello world from get request';
});

Route::get('vue', function() {
    return view('vue');
});