<?php

use Core\Support\Facades\Route;
use App\Controllers\HomeController;
use App\Middleware\{AuthMiddleware};

Route::group(['middleware' => [AuthMiddleware::class]], function () {
    Route::get('main', [HomeController::class, 'main'])->name('main');
    Route::post('logout', [HomeController::class, 'logout'])->name('logout');
    // Route::get('wsToken', [HomeController::class, 'wsToken']);
    // Route::get('test', [HomeController::class, 'test']);
});

Route::group(['middleware' => 'guest'], function () {
    Route::get('wsToken', [HomeController::class, 'wsToken']);
    Route::get('', [HomeController::class, 'entry'])->name('entry');
    Route::post('login', [HomeController::class, 'login'])->name('login');
    // Route::get('test1/{id}', [HomeController::class, 'test1'])->name('test');
    // Route::get('test2/{id}/{name}', [HomeController::class, 'test2'])->name('test2');
    Route::get('test', [HomeController::class, 'testForm']);
    Route::post('test', [HomeController::class, 'test']);
    Route::post('registration', [HomeController::class, 'registration']);
});
