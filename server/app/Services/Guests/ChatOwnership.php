<?php

namespace App\Services\Guests;

use App\Models\Chat;
use App\Services\Guests\CurrentGuest;
use Illuminate\Http\Request;

/**
 * Владение чатом.
 *
 * Владельцем может быть пользователь или гость, поэтому обычная
 * политика Laravel здесь не годится: она рассчитана на User.
 */
class ChatOwnership
{
    public static function owns(Request $request, Chat $chat): bool
    {
        if ($user = $request->user()) {
            return $chat->user_id === $user->id;
        }

        $guest = CurrentGuest::get($request);

        return $guest !== null
            && $chat->guest_id === $guest->id
            // Гостевой чат, уже перенесённый в учётную запись, гостю
            // недоступен: он принадлежит зарегистрировавшемуся.
            && $chat->user_id === null;
    }
}
