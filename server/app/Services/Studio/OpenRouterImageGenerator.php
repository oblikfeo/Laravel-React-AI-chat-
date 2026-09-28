<?php

namespace App\Services\Studio;

use Illuminate\Support\Facades\Http;

/**
 * Генерация изображений через провайдера, совместимого с форматом
 * OpenAI: картинка приходит в ответе чата строкой data:.
 */
class OpenRouterImageGenerator implements ImageGenerator
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly ?string $apiKey,
        private readonly int $timeout,
    ) {
    }

    public function isAvailable(): bool
    {
        return filled($this->apiKey);
    }

    public function generate(GenerationRequest $request): GeneratedImage
    {
        $response = Http::withToken($this->apiKey)
            ->timeout($this->timeout)
            ->acceptJson()
            ->post($this->baseUrl.'/chat/completions', [
                'model' => $request->providerModel,
                'messages' => [[
                    'role' => 'user',
                    'content' => $this->describe($request),
                ]],
                'modalities' => ['image', 'text'],
            ]);

        if ($response->failed()) {
            throw new GenerationFailed(
                'Провайдер вернул '.$response->status().': '.$response->body()
            );
        }

        if ($error = $response->json('error.message')) {
            throw new GenerationFailed('Провайдер сообщил об ошибке: '.$error);
        }

        $url = $response->json('choices.0.message.images.0.image_url.url');

        if (! is_string($url) || $url === '') {
            throw new GenerationFailed('Провайдер не вернул изображение.');
        }

        return $this->decode($url);
    }

    /**
     * Собирает текст запроса.
     *
     * Провайдер принимает всё одной строкой, поэтому то, чего быть не
     * должно, и соотношение сторон дописываются к описанию.
     */
    private function describe(GenerationRequest $request): string
    {
        $parts = [$request->prompt];

        if ($request->negativePrompt) {
            $parts[] = 'Avoid: '.$request->negativePrompt.'.';
        }

        $parts[] = 'Aspect ratio '.$request->aspectRatio.'.';

        // Одно и то же зерно даёт повторяемый результат: человек может
        // вернуться к понравившемуся варианту и поменять в нём детали.
        if ($request->seed !== null) {
            $parts[] = 'Seed '.$request->seed.'.';
        }

        return implode(' ', $parts);
    }

    /** Разбирает строку data:image/...;base64,... */
    private function decode(string $url): GeneratedImage
    {
        if (! preg_match('#^data:(image/[a-z+]+);base64,(.+)$#is', $url, $m)) {
            throw new GenerationFailed('Неизвестный формат изображения.');
        }

        $binary = base64_decode($m[2], true);

        if ($binary === false || $binary === '') {
            throw new GenerationFailed('Изображение не удалось прочитать.');
        }

        $size = @getimagesizefromstring($binary);

        return new GeneratedImage(
            contents: $binary,
            mime: $m[1],
            width: $size[0] ?? null,
            height: $size[1] ?? null,
        );
    }
}
