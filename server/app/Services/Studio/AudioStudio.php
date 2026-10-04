<?php

namespace App\Services\Studio;

/**
 * Долгие звуковые инструменты: музыка, эффекты, смена голоса.
 *
 * Отдельно от картинок: там результат приходит сразу, а здесь задача
 * ставится в очередь и забирается потом.
 */
interface AudioStudio
{
    public function isAvailable(): bool;

    /**
     * Ставит музыку в работу.
     *
     * @throws GenerationFailed
     */
    public function queueMusic(MusicRequest $request): QueuedJob;

    /**
     * Ставит звуковой эффект в работу.
     *
     * @throws GenerationFailed
     */
    public function queueEffect(EffectRequest $request): QueuedJob;

    /**
     * Ставит смену голоса в работу.
     *
     * @param  string  $recording  содержимое исходной записи
     * @throws GenerationFailed
     */
    public function queueVoiceChange(
        string $recording,
        string $filename,
        ?string $voice = null,
        bool $removeNoise = false,
    ): QueuedJob;

    /**
     * Спрашивает, готова ли задача.
     *
     * @throws GenerationFailed
     */
    public function retrieve(string $queueId, bool $voiceChange = false): QueueResult;
}
