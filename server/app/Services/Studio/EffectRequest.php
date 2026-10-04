<?php

namespace App\Services\Studio;

/**
 * Звуковой эффект.
 */
class EffectRequest
{
    public function __construct(
        public readonly string $providerModel,
        public readonly string $prompt,
        public readonly ?int $duration = null,
        /** Склеить начало с концом, чтобы звучало без шва. */
        public readonly bool $loop = false,
    ) {
    }
}
