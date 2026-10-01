<?php

namespace App\Services\Studio;

/**
 * Озвучка текста.
 */
class SpeechRequest
{
    public function __construct(
        public readonly string $text,
        public readonly string $providerModel,
        public readonly ?string $voice = null,
        public readonly float $speed = 1.0,
        public readonly string $format = 'mp3',
    ) {
    }
}
