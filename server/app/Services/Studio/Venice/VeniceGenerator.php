<?php

namespace App\Services\Studio\Venice;

use App\Services\Studio\EditRequest;
use App\Services\Studio\GeneratedAudio;
use App\Services\Studio\GeneratedImage;
use App\Services\Studio\GenerationFailed;
use App\Services\Studio\GenerationRequest;
use App\Services\Studio\ImageGenerator;
use App\Services\Studio\SpeechRequest;

/**
 * Инструменты Студии на Venice AI.
 *
 * Один ключ закрывает весь раздел: рисование, правку, увеличение,
 * удаление фона и озвучку. Подробности в docs/STUDIO-API.md.
 *
 * Форматы ответа у провайдера разные: рисование отдаёт JSON со
 * списком, остальные инструменты — готовый файл телом ответа.
 */
class VeniceGenerator implements ImageGenerator
{
    /** Чем правим изображение, если модель не выбрана явно. */
    private const EDIT_MODEL = 'firered-image-edit';

    public function __construct(
        private readonly VeniceClient $client,
    ) {
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function generate(GenerationRequest $request): array
    {
        $payload = array_filter([
            'model' => $request->providerModel,
            'prompt' => $request->prompt,
            'negative_prompt' => $request->negativePrompt,
            'aspect_ratio' => $request->aspectRatio,
            'style_preset' => $request->stylePreset,
            'variants' => $request->variants > 1 ? $request->variants : null,
            // Ноль у провайдера означает «выбери сам», поэтому своё
            // зерно отправляем только когда человек его задал.
            'seed' => $request->seed,
            'format' => 'png',
            'safe_mode' => $request->safeMode,
            'hide_watermark' => true,
        ], static fn ($value) => $value !== null);

        // safe_mode=false отфильтровался бы как пустое значение, а он
        // смысловой: наше предложение — модели без цензуры.
        $payload['safe_mode'] = $request->safeMode;

        if ($request->styleReferences) {
            $payload['style_references'] = array_map(
                static fn (string $image): array => [
                    'image' => base64_encode($image),
                    'weight' => 0.6,
                ],
                $request->styleReferences,
            );
        }

        $images = $this->client->images('/image/generate', $payload);

        return array_map(
            static fn (string $contents): GeneratedImage => new GeneratedImage(
                contents: $contents,
                mime: 'image/png',
            ),
            $images,
        );
    }

    public function edit(EditRequest $request): GeneratedImage
    {
        $payload = array_filter([
            'model' => $request->providerModel ?: self::EDIT_MODEL,
            'prompt' => $request->prompt,
            'image' => base64_encode($request->image),
            'aspect_ratio' => $request->aspectRatio,
            'output_format' => 'png',
        ], static fn ($value) => $value !== null);

        $payload['safe_mode'] = $request->safeMode;

        return new GeneratedImage(
            contents: $this->client->binary('/image/edit', $payload),
            mime: 'image/png',
        );
    }

    public function combine(array $images, string $prompt, ?string $aspectRatio = null): GeneratedImage
    {
        if (count($images) < 2) {
            throw new GenerationFailed('Для объединения нужно хотя бы два изображения.');
        }

        $payload = array_filter([
            'modelId' => self::EDIT_MODEL,
            'prompt' => $prompt,
            'images' => array_map(
                static fn (string $image): string => base64_encode($image),
                array_values($images),
            ),
            'aspect_ratio' => $aspectRatio,
            'output_format' => 'png',
        ], static fn ($value) => $value !== null);

        $payload['safe_mode'] = false;

        return new GeneratedImage(
            contents: $this->client->binary('/image/multi-edit', $payload),
            mime: 'image/png',
        );
    }

    public function upscale(string $image, int $scale = 2, float $creativity = 0.01): GeneratedImage
    {
        // Провайдер отдаёт увеличенный файл телом ответа, а не в JSON.
        $contents = $this->client->binary('/image/upscale', [
            'image' => base64_encode($image),
            'scale' => $scale,
            'creativity' => $creativity,
        ]);

        return new GeneratedImage(contents: $contents, mime: 'image/png');
    }

    public function removeBackground(string $image): GeneratedImage
    {
        $contents = $this->client->binary('/image/background-remove', [
            'image' => base64_encode($image),
        ]);

        return new GeneratedImage(contents: $contents, mime: 'image/png');
    }

    public function speech(SpeechRequest $request): GeneratedAudio
    {
        $contents = $this->client->binary('/audio/speech', array_filter([
            'model' => $request->providerModel,
            'input' => $request->text,
            'voice' => $request->voice,
            'speed' => $request->speed,
            'response_format' => $request->format,
        ], static fn ($value) => $value !== null));

        return new GeneratedAudio(
            contents: $contents,
            mime: match ($request->format) {
                'wav' => 'audio/wav',
                'flac' => 'audio/flac',
                'aac' => 'audio/aac',
                'opus' => 'audio/opus',
                default => 'audio/mpeg',
            },
        );
    }
}
