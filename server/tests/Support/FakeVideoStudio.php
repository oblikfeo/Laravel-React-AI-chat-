<?php

namespace Tests\Support;

use App\Services\Studio\GenerationFailed;
use App\Services\Studio\QueuedJob;
use App\Services\Studio\QueueResult;
use App\Services\Studio\VideoRequest;
use App\Services\Studio\VideoStudio;

/**
 * Видеостудия для тестов.
 *
 * Ничего не снимает и в сеть не выходит: запоминает, что просили, и
 * отвечает готовностью по команде.
 */
class FakeVideoStudio implements VideoStudio
{
    public ?VideoRequest $lastRequest = null;

    public ?string $lastRetrieveModel = null;

    public ?string $lastDownloadUrl = null;

    public int $queued = 0;

    /** Готов ли ролик, когда о нём спросят. */
    public bool $ready = false;

    /** Чем ответить на вопрос о готовности вместо результата. */
    public ?GenerationFailed $retrieveFailure = null;

    /** Адрес файла, который провайдер называет при постановке. */
    public ?string $downloadUrl = null;

    public function __construct(
        private readonly bool $available = true,
        private readonly bool $fails = false,
    ) {
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function queue(VideoRequest $request): QueuedJob
    {
        $this->lastRequest = $request;

        if ($this->fails) {
            throw new GenerationFailed('Провайдер недоступен.');
        }

        $this->queued++;

        return new QueuedJob(
            id: 'video-'.uniqid(),
            expectedMs: 90000,
            downloadUrl: $this->downloadUrl,
        );
    }

    public function retrieve(
        string $queueId,
        string $providerModel,
        ?string $downloadUrl = null,
    ): QueueResult {
        $this->lastRetrieveModel = $providerModel;
        $this->lastDownloadUrl = $downloadUrl;

        if ($this->retrieveFailure) {
            throw $this->retrieveFailure;
        }

        return $this->ready
            ? QueueResult::ready(contents: 'fake-video', mime: 'video/mp4')
            : QueueResult::pending(status: 'PROCESSING', expectedMs: 60000);
    }
}
