<?php

namespace App\Services\Studio;

/**
 * Звук без ключа провайдера.
 */
class UnavailableAudioStudio implements AudioStudio
{
    public function isAvailable(): bool
    {
        return false;
    }

    public function queueMusic(MusicRequest $request): QueuedJob
    {
        throw $this->fail();
    }

    public function queueEffect(EffectRequest $request): QueuedJob
    {
        throw $this->fail();
    }

    public function queueVoiceChange(
        string $recording,
        string $filename,
        ?string $voice = null,
        bool $removeNoise = false,
    ): QueuedJob {
        throw $this->fail();
    }

    public function retrieve(string $queueId, bool $voiceChange = false): QueueResult
    {
        throw $this->fail();
    }

    private function fail(): GenerationFailed
    {
        return new GenerationFailed('Студия не подключена: нет ключа провайдера.');
    }
}
