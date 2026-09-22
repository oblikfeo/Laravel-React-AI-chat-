<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Приложение работает за Nginx. Без этого Laravel считает
        // соединение незащищённым и строит ссылки по http, а браузер
        // блокирует их на странице, открытой по https.
        $middleware->trustProxies(at: '*');

        // Неавторизованных отправляем на общую страницу входа.
        $middleware->redirectGuestsTo(fn (Request $request) => route('auth'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
