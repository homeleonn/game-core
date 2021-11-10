<?php

use Homeleon\Support\Facades\Route;
use App\Http\Controllers\{
    HomeController,
    UserController,
};
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
    Route::get('test', [HomeController::class, 'testForm']);
    Route::post('test', [HomeController::class, 'test']);
    Route::get('registration', [HomeController::class, 'registrationform']);
    Route::post('registration', [HomeController::class, 'registration']);
});

Route::get('user/{id}/info', [UserController::class, 'info']);
