<?php

namespace App\Services\Studio;

/**
 * Какой ролик снять.
 */
class VideoRequest
{
    public function __construct(
        public readonly string $providerModel,
        public readonly string $prompt,
        /** Длина в секундах. */
        public readonly int $duration,
        public readonly string $resolution,
        /** У оживления пропорции задаёт сама картинка. */
        public readonly ?string $aspectRatio = null,
        /** Содержимое картинки, которую нужно оживить. */
        public readonly ?string $image = null,
        public readonly string $imageMime = 'image/png',
    ) {}
}
