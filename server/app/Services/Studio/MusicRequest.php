<?php

namespace App\Services\Studio;

/**
 * Что сыграть.
 */
class MusicRequest
{
    public function __construct(
        public readonly string $providerModel,
        public readonly string $prompt,
        /** Слова песни: нужны не всякой модели. */
        public readonly ?string $lyrics = null,
        public readonly ?int $duration = null,
        public readonly bool $instrumental = false,
    ) {
    }
}
