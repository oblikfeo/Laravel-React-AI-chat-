<?php

namespace App\Actions\Studio;

use App\Models\Generation;
use Illuminate\Support\Facades\Cache;

/**
 * Забирает готовую долгую работу: звук или видео.
 *
 * О готовности спрашивают двое — открытая страница и фоновая
 * команда. Замок нужен, чтобы они не скачали и не сохранили один и
 * тот же файл дважды.
 */
class CollectQueued
{
    public function __construct(
        private readonly CollectAudio $audio,
        private readonly CollectVideo $video,
    ) {}

    public function handle(Generation $generation): Generation
    {
        $lock = Cache::lock("studio.collect.{$generation->id}", 180);

        if (! $lock->get()) {
            return $generation;
        }

        try {
            // Пока ждали замок, работу мог забрать другой.
            $generation->refresh();

            return $generation->kind === Generation::KIND_VIDEO
                ? $this->video->handle($generation)
                : $this->audio->handle($generation);
        } finally {
            $lock->release();
        }
    }
}
