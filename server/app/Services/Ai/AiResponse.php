<?php

namespace App\Services\Ai;

readonly class AiResponse
{
    public function __construct(
        public string $content,
        public ?string $model = null,
    ) {
    }
}
