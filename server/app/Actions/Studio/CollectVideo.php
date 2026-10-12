<?php

namespace App\Actions\Studio;

use App\Models\Generation;
use App\Services\Studio\GenerationFailed;
use App\Services\Studio\VideoStudio;
use Illuminate\Support\Facades\Log;

/**
 * Забирает готовый ролик.
 *
 * Пока провайдер считает, работа остаётся в состоянии «готовится».
 */
class CollectVideo
{
    public function __construct(
        private readonly VideoStudio $studio,
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
                $this->providerModel($generation),
                $generation->download_url,
            );
        } catch (GenerationFailed $exception) {
            Log::warning('Студия: не удалось забрать ролик', [
                'generation_id' => $generation->id,
                'temporary' => $exception->temporary,
                'message' => $exception->getMessage(),
            ]);

            // За ролик уже заплачено: из-за минутного сбоя его не
            // бросаем, спросим ещё раз.
            return $exception->temporary
                ? $generation
                : $this->giveUp($generation, $exception->getMessage());
        }

        if (! $result->done) {
            if ($result->expectedMs) {
                $generation->forceFill(['expected_ms' => $result->expectedMs])->save();
            }

            return $generation;
        }

        return $this->files->store(
            $generation,
            $result->contents,
            $result->extension(),
            $result->mime ?? 'video/mp4',
        );
    }

    /**
     * Какой моделью считается задача: у оживления она своя.
     */
    private function providerModel(Generation $generation): string
    {
        $model = config("studio.video.models.{$generation->model_key}", []);

        return $generation->operation === Generation::OP_ANIMATE
            ? ($model['provider_model_image'] ?? '')
            : ($model['provider_model'] ?? '');
    }

    private function expired(Generation $generation): bool
    {
        return $generation->created_at
            ->addSeconds((int) config('studio.video.timeout_seconds'))
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
