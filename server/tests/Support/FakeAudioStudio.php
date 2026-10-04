<?php

namespace Tests\Support;

use App\Services\Studio\AudioStudio;
use App\Services\Studio\EffectRequest;
use App\Services\Studio\GenerationFailed;
use App\Services\Studio\MusicRequest;
use App\Services\Studio\QueuedJob;
use App\Services\Studio\QueueResult;

/**
 * Звуковая студия для тестов.
 *
 * Ничего не считает и в сеть не выходит: запоминает, что просили, и
 * отвечает готовностью по команде.
 */
class FakeAudioStudio implements AudioStudio
{
    public ?MusicRequest $lastMusic = null;

    public ?EffectRequest $lastEffect = null;

    public ?string $lastRecording = null;

    public ?string $lastVoice = null;

    public bool $lastVoiceChangeFlag = false;

    /** Готова ли задача, когда о ней спросят. */
    public bool $ready = false;

    public function __construct(
        private readonly bool $available = true,
        private readonly bool $fails = false,
    ) {
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function queueMusic(MusicRequest $request): QueuedJob
    {
        $this->lastMusic = $request;

        return $this->job();
    }

    public function queueEffect(EffectRequest $request): QueuedJob
    {
        $this->lastEffect = $request;

        return $this->job();
    }

    public function queueVoiceChange(
        string $recording,
        string $filename,
        ?string $voice = null,
        bool $removeNoise = false,
    ): QueuedJob {
        $this->lastRecording = $recording;
        $this->lastVoice = $voice;

        return $this->job();
    }

    public ?string $lastRetrieveModel = null;

    public function retrieve(
        string $queueId,
        string $providerModel,
        bool $voiceChange = false,
    ): QueueResult {
        $this->lastVoiceChangeFlag = $voiceChange;
        $this->lastRetrieveModel = $providerModel;

        return $this->ready
            ? QueueResult::ready(contents: 'fake-audio', mime: 'audio/mpeg')
            : QueueResult::pending(status: 'PROCESSING', expectedMs: 30000);
    }

    private function job(): QueuedJob
    {
        if ($this->fails) {
            throw new GenerationFailed('Провайдер недоступен.');
        }

        return new QueuedJob(id: 'job-'.uniqid(), expectedMs: 30000);
    }
}
