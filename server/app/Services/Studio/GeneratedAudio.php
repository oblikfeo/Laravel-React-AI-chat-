<?php

namespace App\Services\Studio;

/**
 * Готовая запись: содержимое файла и его тип.
 */
class GeneratedAudio
{
    public function __construct(
        public readonly string $contents,
        public readonly string $mime = 'audio/mpeg',
    ) {
    }

    public function extension(): string
    {
        return match ($this->mime) {
            'audio/wav' => 'wav',
            'audio/flac' => 'flac',
            'audio/aac' => 'aac',
            'audio/opus' => 'opus',
            default => 'mp3',
        };
    }
}
