<?php

namespace App\Actions\Studio;

use App\Models\Generation;
use App\Services\Studio\AudioStudio;
use App\Services\Studio\GenerationFailed;
use Illuminate\Support\Facades\Log;

/**
 * Забирает готовую звуковую работу.
 *
 * Пока провайдер считает, работа остаётся в состоянии «готовится»:
 * страница спрашивает о ней, пока не появится файл.
 */
class CollectAudio
{
    public function __construct(
        private readonly AudioStudio $studio,
        private readonly StoreGenerationFile $files,
    ) {
    }

    public function handle(Generation $generation): Generation
    {
        if (! $generation->isQueued() || ! $generation->queue_id) {
            return $generation;
        }

        // Задача, о которой провайдер забыл, висела бы вечно.
        if ($this->expired($generation)) {
            return $this->giveUp($generation, 'Провайдер не ответил вовремя.');
        }

        try {
            $result = $this->studio->retrieve(
                $generation->queue_id,
                $generation->operation === Generation::OP_VOICE_CHANGE,
            );
        } catch (GenerationFailed $exception) {
            Log::warning('Студия: не удалось забрать звук', [
                'generation_id' => $generation->id,
                'message' => $exception->getMessage(),
            ]);

            return $this->giveUp($generation, $exception->getMessage());
        }

        if (! $result->done) {
            // Оценка времени уточняется по ходу работы.
            if ($result->expectedMs) {
                $generation->forceFill(['expected_ms' => $result->expectedMs])->save();
            }

            return $generation;
        }

        return $this->files->store(
            $generation,
            $result->contents,
            $result->extension(),
            $result->mime ?? 'audio/mpeg',
        );
    }

    private function expired(Generation $generation): bool
    {
        return $generation->created_at
            ->addSeconds((int) config('studio.queue.timeout_seconds'))
            ->isPast();
    }

    private function giveUp(Generation $generation, string $reason): Generation
    {
        $generation->forceFill([
            'status' => Generation::STATUS_FAILED,
            'failure_reason' => $reason,
        ])->save();

        return $generation;
    }
}
