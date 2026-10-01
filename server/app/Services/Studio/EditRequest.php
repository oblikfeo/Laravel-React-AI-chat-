<?php

namespace App\Services\Studio;

/**
 * Правка готового изображения.
 */
class EditRequest
{
    public function __construct(
        /** Содержимое исходного файла. */
        public readonly string $image,
        public readonly string $prompt,
        public readonly ?string $providerModel = null,
        public readonly ?string $aspectRatio = null,
        public readonly bool $safeMode = false,
    ) {
    }
}
