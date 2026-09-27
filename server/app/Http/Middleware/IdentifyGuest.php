<?php

namespace App\Http\Middleware;

use App\Models\Guest;
use App\Services\Guests\GuestResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;
use Symfony\Component\HttpFoundation\Response;

/**
 * Опознаёт посетителя без учётной записи и выдаёт ему куку.
 *
 * Авторизованных не трогает: у них есть аккаунт.
 */
class IdentifyGuest
{
    public function __construct(private readonly GuestResolver $resolver)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() || ! config('guests.enabled')) {
            return $next($request);
        }

        try {
            $guest = $this->resolver->resolve($request);
        } catch (Throwable $exception) {
            // Опознание гостя — удобство, а не основа работы сайта.
            // Если база недоступна, страница всё равно должна открыться.
            Log::warning('Не удалось опознать гостя', [
                'message' => $exception->getMessage(),
            ]);

            return $next($request);
        }

        // Кладём в контейнер, а не в атрибуты запроса: FormRequest —
        // отдельный объект, и атрибуты до него не доходят.
        app()->instance(Guest::class, $guest);
        $request->attributes->set('guest', $guest);

        $response = $next($request);

        // Кука живёт год: иначе посетитель терял бы свои чаты после
        // каждого закрытия браузера.
        return $response->withCookie(cookie(
            name: config('guests.cookie_name'),
            value: $guest->token,
            minutes: config('guests.cookie_days') * 24 * 60,
            httpOnly: true,
        ));
    }
}
