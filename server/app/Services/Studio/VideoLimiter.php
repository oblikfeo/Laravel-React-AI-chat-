<?php

namespace App\Services\Studio;

use App\Models\Generation;
use App\Models\User;

/**
 * Сколько роликов в день можно снять.
 *
 * Видео на порядок дороже остального, поэтому счёт у него свой и
 * есть он только у платных тарифов, см. config/studio.php.
 */
class VideoLimiter
{
    /**
     * Дневной лимит. Ноль — видео на тарифе нет.
     */
    public function limitFor(?User $user): int
    {
        if (! $user) {
            return 0;
        }

        return (int) config("studio.video.daily_limits.{$user->plan}", 0);
    }

    public function hasAccess(?User $user): bool
    {
        return $this->limitFor($user) > 0;
    }

    public function usedToday(User $user): int
    {
        return Generation::query()
            ->where('user_id', $user->id)
            ->where('kind', Generation::KIND_VIDEO)
            // Несостоявшийся ролик попытку не сжигает: человек не
            // виноват в том, что у провайдера не получилось.
            ->where('status', '!=', Generation::STATUS_FAILED)
            ->whereDate('created_at', today())
            ->count();
    }

    public function remaining(?User $user): int
    {
        if (! $user) {
            return 0;
        }

        return max(0, $this->limitFor($user) - $this->usedToday($user));
    }

    public function allows(?User $user): bool
    {
        return $this->remaining($user) > 0;
    }
}
