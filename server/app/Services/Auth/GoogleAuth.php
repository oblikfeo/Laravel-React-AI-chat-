<?php

namespace App\Services\Auth;

/**
 * Вход через Google.
 *
 * Пока реквизиты не заданы, кнопка входа не показывается: вести на
 * страницу, которая ответит ошибкой, нельзя.
 *
 * Чтобы включить, достаточно вписать в .env два значения:
 *   GOOGLE_CLIENT_ID=...
 *   GOOGLE_CLIENT_SECRET=...
 */
class GoogleAuth
{
    public static function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }
}
