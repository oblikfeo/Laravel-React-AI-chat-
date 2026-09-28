<?php

namespace App\Services\Studio;

/**
 * Пока генерация изображений недоступна.
 *
 * Интерфейс спрашивает isAvailable() заранее и показывает, что Студия
 * скоро откроется, поэтому до вызова generate() дело не доходит.
 */
class UnavailableGenerator implements ImageGenerator
{
    public function isAvailable(): bool
    {
        return false;
    }

    public function generate(GenerationRequest $request): GeneratedImage
    {
        throw new GenerationFailed('Генерация изображений не подключена.');
    }
}
