<?php

namespace App\Services\Studio;

use App\Models\Generation;
use App\Models\Guest;
use App\Models\User;

/**
 * Сколько работ в день можно создать.
 *
 * Генерация изображений дороже текста, поэтому лимит отдельный и
 * заметно строже: у гостя несколько штук, у бесплатного тарифа —
 * полтора десятка.
 */
class StudioLimiter
{
    public function limitFor(?User $user): int
    {
        if (! $user) {
            return config('studio.daily_limit_guest');
        }

        // Платные тарифы ограничиваются кредитами, а не счётчиком.
        return $user->isPromotedPlan()
            ? config('studio.daily_limit_free')
            : PHP_INT_MAX;
    }

    public function usedToday(?User $user, ?Guest $guest): int
    {
        return Generation::query()
            ->ownedBy($user, $guest)
            ->whereDate('created_at', today())
            ->count();
    }

    public function remaining(?User $user, ?Guest $guest): int
    {
        $limit = $this->limitFor($user);

        if ($limit === PHP_INT_MAX) {
            return PHP_INT_MAX;
        }

        return max(0, $limit - $this->usedToday($user, $guest));
    }

    public function allows(?User $user, ?Guest $guest): bool
    {
        return $this->remaining($user, $guest) > 0;
    }
}
