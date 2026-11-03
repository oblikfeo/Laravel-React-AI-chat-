<?php

namespace App\Services\Studio;

use App\Models\Generation;
use App\Models\Guest;

/**
 * Сколько работ в день может создать гость.
 *
 * Пользователей ограничивает бюджет тарифа, см. UsageMeter. Гость —
 * это проба до регистрации: ему несколько работ в день.
 */
class StudioLimiter
{
    public function limit(): int
    {
        return (int) config('studio.daily_limit_guest');
    }

    public function usedToday(Guest $guest): int
    {
        return Generation::query()
            ->where('guest_id', $guest->id)
            ->whereDate('created_at', today())
            ->count();
    }

    public function remaining(Guest $guest): int
    {
        return max(0, $this->limit() - $this->usedToday($guest));
    }

    public function allows(Guest $guest): bool
    {
        return $this->remaining($guest) > 0;
    }
}
