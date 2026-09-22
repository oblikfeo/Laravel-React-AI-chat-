<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Провайдер для любого API, совместимого с форматом OpenAI:
 * OpenRouter, Ollama, Together, OpenAI и прочие.
 *
 * Переключение между ними — только конфигурация, без правок кода.
 */
class OpenAiCompatibleProvider implements AiChatProvider
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly ?string $apiKey,
        private readonly string $model,
        private readonly int $timeout,
    ) {
    }

    public function isConfigured(): bool
    {
        return filled($this->apiKey);
    }

    public function complete(array $messages): AiResponse
    {
        $response = Http::withToken($this->apiKey)
            ->timeout($this->timeout)
            ->acceptJson()
            ->post($this->baseUrl.'/chat/completions', [
                'model' => $this->model,
                'messages' => $messages,
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Провайдер вернул ошибку '.$response->status().': '.$response->body()
            );
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || $content === '') {
            throw new RuntimeException('Провайдер вернул пустой ответ.');
        }

        return new AiResponse(
            content: trim($content),
            model: $response->json('model') ?? $this->model,
        );
    }
}
