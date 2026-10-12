<?php

namespace App\Services\Studio;

/**
 * Видео без ключа провайдера.
 */
class UnavailableVideoStudio implements VideoStudio
{
    public function isAvailable(): bool
    {
        return false;
    }

    public function queue(VideoRequest $request): QueuedJob
    {
        throw $this->fail();
    }

    public function retrieve(
        string $queueId,
        string $providerModel,
        ?string $downloadUrl = null,
    ): QueueResult {
        throw $this->fail();
    }

    private function fail(): GenerationFailed
    {
        return new GenerationFailed('Студия не подключена: нет ключа провайдера.');
    }
}
