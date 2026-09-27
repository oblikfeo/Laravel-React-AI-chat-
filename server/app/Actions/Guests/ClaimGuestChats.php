<?php

namespace App\Actions\Guests;

use App\Models\Guest;
use App\Models\User;
use App\Services\Guests\CurrentGuest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Переносит переписку гостя в его учётную запись.
 *
 * Человек пробует чат без регистрации, потом заводит аккаунт — и
 * должен увидеть свои разговоры на месте. Потеря переписки в этот
 * момент выглядит как поломка.
 */
class ClaimGuestChats
{
    public function handle(Request $request, User $user): int
    {
        $guest = CurrentGuest::get($request);

        if (! $guest instanceof Guest || $guest->user_id !== null) {
            return 0;
        }

        return DB::transaction(function () use ($guest, $user) {
            $moved = $guest->chats()
                ->whereNull('user_id')
                ->update(['user_id' => $user->id]);

            // Помечаем гостя: его дневной лимит больше не считается,
            // и по отпечатку он не подхватится как новый посетитель.
            $guest->forceFill(['user_id' => $user->id])->save();

            return $moved;
        });
    }
}
