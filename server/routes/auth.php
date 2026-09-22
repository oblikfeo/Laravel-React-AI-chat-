<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ShowAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // Одна страница на вход, регистрацию и восстановление.
    Route::get('auth', ShowAuthController::class)->name('auth');

    Route::post('login', LoginController::class)->name('login.store');
    Route::post('register', RegisterController::class)->name('register.store');
});

Route::post('logout', LogoutController::class)
    ->middleware('auth')
    ->name('logout');
