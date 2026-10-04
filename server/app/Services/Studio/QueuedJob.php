<?php

namespace App\Services\Studio;

/**
 * Задача, принятая провайдером в работу.
 */
class QueuedJob
{
    public function __construct(
        public readonly string $id,
        /** Сколько примерно займёт, в миллисекундах. */
        public readonly ?int $expectedMs = null,
    ) {
    }
}
