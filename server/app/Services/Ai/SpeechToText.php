<?php

namespace App\Services\Ai;

use App\Services\Studio\GenerationFailed;

/**
 * Речь в текст: голосовой ввод в поле сообщения.
 */
interface SpeechToText
{
    public function isAvailable(): bool;

    /**
     * @param  string  $audio  содержимое записи
     *
     * @throws GenerationFailed
     */
    public function transcribe(string $audio, string $filename): string;
}
