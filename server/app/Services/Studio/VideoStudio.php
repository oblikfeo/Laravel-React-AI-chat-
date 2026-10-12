<?php

namespace App\Services\Studio;

/**
 * Видео: ролик по описанию и оживление картинки.
 *
 * Считается минутами, поэтому, как и музыка, идёт через очередь
 * провайдера: задача ставится, результат забирается потом.
 */
interface VideoStudio
{
    public function isAvailable(): bool;

    /**
     * Ставит ролик в работу.
     *
     * @throws GenerationFailed
     */
    public function queue(VideoRequest $request): QueuedJob;

    /**
     * Спрашивает, готов ли ролик.
     *
     * @param  string|null  $downloadUrl  адрес файла, если провайдер
     *                                    сообщил его при постановке
     * @throws GenerationFailed
     */
    public function retrieve(
        string $queueId,
        string $providerModel,
        ?string $downloadUrl = null,
    ): QueueResult;
}
