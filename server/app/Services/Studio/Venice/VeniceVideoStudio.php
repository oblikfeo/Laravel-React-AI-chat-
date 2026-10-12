<?php

namespace App\Services\Studio\Venice;

use App\Services\Studio\QueuedJob;
use App\Services\Studio\QueueResult;
use App\Services\Studio\VideoRequest;
use App\Services\Studio\VideoStudio;

/**
 * Видео на Venice AI.
 *
 * Разбор возможностей провайдера — в docs/STUDIO-API.md.
 */
class VeniceVideoStudio implements VideoStudio
{
    public function __construct(private readonly VeniceQueue $queue)
    {
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function queue(VideoRequest $request): QueuedJob
    {
        return $this->queue->queueVideo(array_filter([
            'model' => $request->providerModel,
            'prompt' => $request->prompt,
            'duration' => $request->duration.'s',
            'resolution' => $request->resolution,
            // Оживлению пропорции не нужны: их задаёт картинка, и
            // лишний параметр провайдер отвергает.
            'aspect_ratio' => $request->image === null ? $request->aspectRatio : null,
            'image_url' => $request->image === null
                ? null
                : sprintf('data:%s;base64,%s', $request->imageMime, base64_encode($request->image)),
        ], static fn ($value) => $value !== null));
    }

    public function retrieve(
        string $queueId,
        string $providerModel,
        ?string $downloadUrl = null,
    ): QueueResult {
        return $this->queue->retrieveVideo($queueId, $providerModel, $downloadUrl);
    }
}
