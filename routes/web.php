<?php

use Homeleon\Support\Facades\Route;
use App\Http\Controllers\{
    HomeController,
    UserController,
};
use App\Middleware\{AuthMiddleware};

Route::group(['middleware' => [AuthMiddleware::class]], function () {
//    Route::get('main', [HomeController::class, 'main'])->name('main');
//    Route::get('logout', [HomeController::class, 'logout'])->name('logout');
});

Route::group(['middleware' => 'guest'], function () {
//    Route::get('', [HomeController::class, 'entry'])->name('entry');
//    Route::post('login', [HomeController::class, 'login'])->name('login');
//    Route::get('registration', [HomeController::class, 'registrationform'])->name('registration');
//    Route::post('registration', [HomeController::class, 'registration']);
//    Route::get('captcha', [HomeController::class, 'getCaptcha']);
});

Route::get('user/{id}/info', [UserController::class, 'info']);
