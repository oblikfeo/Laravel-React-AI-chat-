<?php

namespace App\Services\Usage;

use App\Models\Guest;
use App\Models\User;
use App\Services\Studio\StudioLimiter;

/**
 * Бюджет для страницы Студии: остаток и цена работ в процентах.
 *
 * Остаток у всех в одном виде — проценты и дата обновления, — чтобы
 * формы не разбирались, гость перед ними или подписчик.
 */
class StudioUsage
{
    public function __construct(
        private readonly UsageMeter $meter,
        private readonly StudioLimiter $guests,
    ) {}

    /**
     * share — сколько процентов бюджета стоит доллар; null — цены не
     * показываем (гостю, у которого счёт в работах, а не в деньгах).
     *
     * @return array{share: float|null, limit: array{percentLeft: int, renewsAt: string, exhausted: bool}|null}
     */
    public function for(?User $user, ?Guest $guest): array
    {
        if ($user) {
            $usage = $this->meter->snapshot($user);
            $daily = $usage->dailyLimit !== null && $usage->dailyExhausted();

            return [
                'share' => $usage->shareOf(1.0) ?: null,
                'limit' => [
                    'percentLeft' => 100 - $usage->percentUsed(),
                    'renewsAt' => $daily ? 'tomorrow' : $usage->periodEnd->format('j M'),
                    'exhausted' => $usage->exhausted() || $daily,
                ],
            ];
        }

        if (! $guest) {
            return ['share' => null, 'limit' => null];
        }

        $limit = max(1, $this->guests->limit());
        $remaining = $this->guests->remaining($guest);

        return [
            'share' => null,
            'limit' => [
                'percentLeft' => (int) floor($remaining / $limit * 100),
                'renewsAt' => 'tomorrow',
                'exhausted' => $remaining === 0,
            ],
        ];
    }
}
