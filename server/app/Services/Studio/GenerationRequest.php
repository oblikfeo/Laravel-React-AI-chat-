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
        /** Сколько вариантов одной идеи показать. */
        public readonly int $variants = 1,
        /** Название стиля из справочника провайдера. */
        public readonly ?string $stylePreset = null,
        /**
         * Образцы, на которые надо быть похожим.
         *
         * @var array<int, string> содержимое файлов
         */
        public readonly array $styleReferences = [],
        /** Фильтр провайдера: наше предложение — модели без цензуры. */
        public readonly bool $safeMode = false,
    ) {
    }
}
