<?php

namespace App\Services\Usage;

use App\Models\Guest;
use App\Models\User;
use App\Services\Guests\GuestLimiter;
use App\Services\Studio\StudioLimiter;

/**
 * Можно ли потратить ещё: ответ в чате, работа Студии, ролик.
 *
 * Пользователя ограничивает бюджет тарифа, гостя — пробный лимит.
 * Каждый метод возвращает причину отказа словами для человека или
 * null, если можно.
 */
class UsageGate
{
    public function __construct(
        private readonly UsageMeter $meter,
        private readonly StudioLimiter $studio,
        private readonly GuestLimiter $guests,
    ) {}

    public function chat(?User $user, ?Guest $guest): ?string
    {
        if ($user) {
            return $this->meter->denial($user);
        }

        return $guest && ! $this->guests->allows($guest)
            ? "You have reached today's free limit. Sign up to keep going."
            : null;
    }

    public function studio(?User $user, ?Guest $guest): ?string
    {
        if ($user) {
            return $this->meter->denial($user);
        }

        return $guest && ! $this->studio->allows($guest)
            ? "You have reached today's free limit. Sign up to create more."
            : null;
    }

    public function video(?User $user): ?string
    {
        if (! $user || ! $user->hasVideo()) {
            return 'Video is available on paid plans.';
        }

        return $this->meter->denial($user);
    }
}
