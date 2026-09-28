<?php

namespace App\Services\Studio;

/**
 * Что нужно нарисовать.
 */
class GenerationRequest
{
    public function __construct(
        public readonly string $providerModel,
        public readonly string $prompt,
        public readonly ?string $negativePrompt = null,
        public readonly string $aspectRatio = '1:1',
        public readonly ?int $seed = null,
        public readonly int $width = 1024,
        public readonly int $height = 1024,
    ) {
    }
}
