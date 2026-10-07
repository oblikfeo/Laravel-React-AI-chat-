<?php

namespace App\Policies;

use App\Models\Character;
use App\Models\User;

class CharacterPolicy
{
    /**
     * Открыть персонажа может автор, а чужого — только если он в
     * общем каталоге. Пользователя может и не быть: каталог виден
     * гостю.
     */
    public function view(?User $user, Character $character): bool
    {
        return $character->isOpenTo($user);
    }

    public function update(User $user, Character $character): bool
    {
        return $character->isOwnedBy($user);
    }

    public function delete(User $user, Character $character): bool
    {
        return $character->isOwnedBy($user);
    }
}
