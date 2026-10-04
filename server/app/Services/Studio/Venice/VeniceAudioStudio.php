<?php

namespace App\Services\Studio\Venice;

use App\Services\Studio\AudioStudio;
use App\Services\Studio\EffectRequest;
use App\Services\Studio\MusicRequest;
use App\Services\Studio\QueuedJob;
use App\Services\Studio\QueueResult;

/**
 * Звуковые инструменты на Venice AI.
 *
 * Разбор возможностей провайдера — в docs/STUDIO-API.md.
 */
class VeniceAudioStudio implements AudioStudio
{
    public function __construct(private readonly VeniceAudioQueue $queue)
    {
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function queueMusic(MusicRequest $request): QueuedJob
    {
        return $this->queue->queueAudio(array_filter([
            'model' => $request->providerModel,
            'prompt' => $request->prompt,
            'lyrics_prompt' => $request->lyrics,
            'duration_seconds' => $request->duration,
            'force_instrumental' => $request->instrumental ?: null,
        ], static fn ($value) => $value !== null));
    }

    public function queueEffect(EffectRequest $request): QueuedJob
    {
        return $this->queue->queueAudio(array_filter([
            'model' => $request->providerModel,
            'prompt' => $request->prompt,
            'duration_seconds' => $request->duration,
            'loop' => $request->loop ?: null,
        ], static fn ($value) => $value !== null));
    }

    public function queueVoiceChange(
        string $recording,
        string $filename,
        ?string $voice = null,
        bool $removeNoise = false,
    ): QueuedJob {
        return $this->queue->queueVoiceChange(
            payload: array_filter([
                'model' => config('studio.voice_changer.provider_model'),
                'voice' => $voice,
                'remove_background_noise' => $removeNoise ?: null,
            ], static fn ($value) => $value !== null),
            recording: $recording,
            filename: $filename,
        );
    }

    public function retrieve(string $queueId, bool $voiceChange = false): QueueResult
    {
        return $this->queue->retrieve($queueId, $voiceChange);
    }
}
