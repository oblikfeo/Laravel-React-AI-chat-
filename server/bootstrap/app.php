<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\IdentifyGuest::class,
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
        // Последняя страховка. Если действие на странице (отправка
        // формы, запуск генерации) упало с непредвиденной ошибкой,
        // человек остаётся там, где был, и видит сообщение — а не
        // окно с ошибкой 500 поверх работы. Сама ошибка при этом
        // записывается в журнал как обычно.
        $exceptions->render(function (Throwable $exception, Request $request) {
            $expected = $exception instanceof HttpExceptionInterface
                || $exception instanceof ValidationException
                || $exception instanceof AuthenticationException
                || $exception instanceof AuthorizationException
                || $exception instanceof ModelNotFoundException
                || $exception instanceof TokenMismatchException;

            if ($expected || $request->isMethod('GET') || ! $request->header('X-Inertia')) {
                return null;
            }

            return back()->with('error', 'Something went wrong on our side. Please try again.');
        });
    })->create();
