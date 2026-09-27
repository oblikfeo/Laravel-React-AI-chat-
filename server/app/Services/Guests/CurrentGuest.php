<?php

namespace App\Services\Guests;

use App\Models\Guest;
use Illuminate\Http\Request;

/**
 * Текущий посетитель без учётной записи.
 *
 * Берётся из контейнера, а не из атрибутов запроса: FormRequest —
 * отдельный объект, и атрибуты, проставленные промежуточным слоем,
 * до него не доходят.
 */
class CurrentGuest
{
    public static function get(?Request $request = null): ?Guest
    {
        if (app()->bound(Guest::class)) {
            return app(Guest::class);
        }

        $value = $request?->attributes->get('guest');

        return $value instanceof Guest ? $value : null;
    }
}
