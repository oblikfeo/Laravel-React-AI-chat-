<?php

namespace App\Services\Usage;

use App\Models\Guest;
use App\Models\User;
use App\Services\Guests\GuestLimiter;

/**
 * Можно ли потратить ещё: ответ в чате, работа Студии, ролик, голос.
 *
 * Три предела по очереди: дорогие настройки и дневной счёт без
 * подписки (FreeTier), затем бюджет тарифа с ценой этого запроса.
 * Гостя в чате держит GuestLimiter — у него ещё предел по адресу.
 *
 * Каждый метод возвращает причину отказа словами для человека или
 * null, если можно.
 */
class UsageGate
{
    public function __construct(
        private readonly UsageMeter $meter,
        private readonly FreeTier $free,
        private readonly GuestLimiter $guests,
    ) {}

    public function chat(?User $user, ?Guest $guest): ?string
    {
        if ($user) {
            return $this->free->denial($user, null, 'chat') ?? $this->meter->denial($user);
        }

        return $guest && ! $this->guests->allows($guest)
            ? "You have reached today's free limit. Sign up to keep going."
            : null;
    }

    /**
     * @param  array<string, mixed>  $input  настройки запроса из формы
     */
    public function studio(?User $user, ?Guest $guest, string $operation, array $input = []): ?string
    {
        return $this->free->denial($user, $guest, FreeTier::toolOf($operation), $operation, $input)
            ?? ($user ? $this->meter->denial($user, GenerationCost::estimate($operation, $input)) : null);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function video(?User $user, string $operation, array $input = []): ?string
    {
        if (! $user || ! $user->hasVideo()) {
            return 'Video is available on paid plans.';
        }

        return $this->meter->denial($user, GenerationCost::estimate($operation, $input));
    }

    public function voice(?User $user, ?Guest $guest): ?string
    {
        return $this->free->denial($user, $guest, 'voice')
            ?? ($user ? $this->meter->denial($user) : null);
    }
}
