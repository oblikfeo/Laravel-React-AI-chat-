<?php

namespace App\Services\Ai;

use App\Services\Studio\GenerationFailed;

/**
 * Голосовой ввод без ключа провайдера.
 */
class UnavailableSpeechToText implements SpeechToText
{
    public function isAvailable(): bool
    {
        return false;
    }

    public function transcribe(string $audio, string $filename): string
    {
        throw new GenerationFailed('Голосовой ввод не подключён: нет ключа провайдера.');
    }
}
