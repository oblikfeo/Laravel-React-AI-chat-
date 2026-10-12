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
        /** Где будет лежать готовый файл, если провайдер назвал адрес. */
        public readonly ?string $downloadUrl = null,
    ) {
    }
}
