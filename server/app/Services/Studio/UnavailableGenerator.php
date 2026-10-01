<?php

namespace App\Services\Studio;

/**
 * Студия без ключа провайдера.
 *
 * Интерфейс спрашивает isAvailable() заранее и показывает, что раздел
 * скоро откроется, поэтому до вызова инструментов дело не доходит.
 */
class UnavailableGenerator implements ImageGenerator
{
    public function isAvailable(): bool
    {
        return false;
    }

    public function generate(GenerationRequest $request): array
    {
        throw $this->fail();
    }

    public function edit(EditRequest $request): GeneratedImage
    {
        throw $this->fail();
    }

    public function combine(array $images, string $prompt, ?string $aspectRatio = null): GeneratedImage
    {
        throw $this->fail();
    }

    public function upscale(string $image, int $scale = 2, float $creativity = 0.01): GeneratedImage
    {
        throw $this->fail();
    }

    public function removeBackground(string $image): GeneratedImage
    {
        throw $this->fail();
    }

    public function speech(SpeechRequest $request): GeneratedAudio
    {
        throw $this->fail();
    }

    private function fail(): GenerationFailed
    {
        return new GenerationFailed('Студия не подключена: нет ключа провайдера.');
    }
}
