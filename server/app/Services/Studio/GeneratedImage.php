<?php

namespace App\Services\Studio;

/**
 * Готовое изображение: содержимое файла и его тип.
 */
class GeneratedImage
{
    public function __construct(
        public readonly string $contents,
        public readonly string $mime = 'image/png',
        public readonly ?int $width = null,
        public readonly ?int $height = null,
    ) {
    }

    public function extension(): string
    {
        return match ($this->mime) {
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default => 'png',
        };
    }
}
