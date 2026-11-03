<?php

namespace App\Services\Ai;

/**
 * Ответ текстовой модели.
 *
 * Кроме текста — расход: сколько токенов модель прочитала и написала
 * и сколько это стоило, если провайдер назвал цену сам.
 */
readonly class AiResponse
{
    public function __construct(
        public string $content,
        public ?string $model = null,
        public int $inputTokens = 0,
        public int $outputTokens = 0,
        public ?float $cost = null,
    ) {}
}
