<?php

namespace App\Console\Commands;

use App\Actions\Studio\CollectQueued;
use App\Models\Generation;
use Illuminate\Console\Command;

/**
 * Забирает у провайдера готовые музыку, эффекты и ролики.
 *
 * Страница Студии спрашивает о готовности сама, но только пока
 * открыта. Команда делает то же по расписанию, поэтому работа
 * дойдёт до галереи, даже если человек ушёл.
 */
class CollectStudioWork extends Command
{
    protected $signature = 'studio:collect';

    protected $description = 'Забирает готовые работы Студии у провайдера';

    public function handle(CollectQueued $collect): int
    {
        $waiting = Generation::query()
            ->where('status', Generation::STATUS_QUEUED)
            ->oldest('id')
            ->get();

        $finished = 0;

        foreach ($waiting as $generation) {
            if ($collect->handle($generation)->isReady()) {
                $finished++;
            }
        }

        $this->info("В работе: {$waiting->count()}, готово: {$finished}");

        return self::SUCCESS;
    }
}
