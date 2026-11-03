<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Пускает в админку только администраторов.
 *
 * Остальным — «страница не найдена», а не «доступ запрещён»: не нужно
 * подсказывать, что по этому адресу что-то есть.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin()) {
            throw new NotFoundHttpException;
        }

        return $next($request);
    }
}
