<?php

namespace Tests\Support;

use App\Services\Studio\EditRequest;
use App\Services\Studio\GeneratedAudio;
use App\Services\Studio\GeneratedImage;
use App\Services\Studio\GenerationFailed;
use App\Services\Studio\GenerationRequest;
use App\Services\Studio\ImageGenerator;
use App\Services\Studio\SpeechRequest;

/**
 * Провайдер для тестов.
 *
 * Возвращает однопиксельный PNG вместо обращения к сети и запоминает
 * последний запрос: так проверяется, что до провайдера дошло именно
 * то, что выбрал человек.
 */
class FakeImageGenerator implements ImageGenerator
{
    /** Однопиксельный прозрачный PNG. */
    public const PIXEL = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public ?GenerationRequest $lastGeneration = null;

    public ?EditRequest $lastEdit = null;

    public ?SpeechRequest $lastSpeech = null;

    /** @var array<int, string>|null */
    public ?array $lastCombine = null;

    public ?int $lastScale = null;

    public bool $backgroundRemoved = false;

    public function __construct(
        private readonly bool $available = true,
        private readonly bool $fails = false,
    ) {
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function generate(GenerationRequest $request): array
    {
        $this->lastGeneration = $request;

        $this->guard();

        return array_fill(0, max($request->variants, 1), $this->image());
    }

    public function edit(EditRequest $request): GeneratedImage
    {
        $this->lastEdit = $request;

        $this->guard();

        return $this->image();
    }

    public function combine(array $images, string $prompt, ?string $aspectRatio = null): GeneratedImage
    {
        $this->lastCombine = $images;

        $this->guard();

        return $this->image();
    }

    public function upscale(string $image, int $scale = 2, float $creativity = 0.01): GeneratedImage
    {
        $this->lastScale = $scale;

        $this->guard();

        return $this->image();
    }

    public function removeBackground(string $image): GeneratedImage
    {
        $this->backgroundRemoved = true;

        $this->guard();

        return $this->image();
    }

    public function speech(SpeechRequest $request): GeneratedAudio
    {
        $this->lastSpeech = $request;

        $this->guard();

        return new GeneratedAudio(contents: 'fake-audio', mime: 'audio/mpeg');
    }

    private function image(): GeneratedImage
    {
        return new GeneratedImage(
            contents: base64_decode(self::PIXEL),
            mime: 'image/png',
            width: 1,
            height: 1,
        );
    }

    private function guard(): void
    {
        if ($this->fails) {
            throw new GenerationFailed('Провайдер недоступен.');
        }
    }
}
