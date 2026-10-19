<?php

namespace App\Services\Guests;

use App\Models\Generation;
use Illuminate\Http\Request;

/**
 * Чья работа Студии.
 *
 * Владелец — пользователь или гость. Гостю принадлежат только работы,
 * созданные до регистрации и ещё не привязанные к учётной записи.
 */
class GenerationOwnership
{
    public static function owns(Request $request, Generation $generation): bool
    {
        if ($user = $request->user()) {
            return $generation->user_id === $user->id;
        }

        $guest = CurrentGuest::get($request);

        return $guest !== null
            && $generation->guest_id === $guest->id
            && $generation->user_id === null;
    }
}
