<?php

use App\Http\Controllers\Api\V1\LoginController;
use Homeleon\Support\Facades\Route;

Route::post('forced-login', [LoginController::class, 'forcedLogin'])->name('forced-login');
Route::get('wsToken', [LoginController::class, 'wsToken']);
