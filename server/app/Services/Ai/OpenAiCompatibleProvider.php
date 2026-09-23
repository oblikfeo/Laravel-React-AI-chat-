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
        private readonly int $maxTokens = 1024,
    ) {
    }

    public function isConfigured(): bool
    {
        return filled($this->apiKey);
    }

    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @param  string|null  $model  Название модели у провайдера.
     *                              Пусто — берём из настроек.
     */
    public function complete(array $messages, ?string $model = null): AiResponse
    {
        $response = Http::withToken($this->apiKey)
            ->timeout($this->timeout)
            ->acceptJson()
            ->post($this->baseUrl.'/chat/completions', [
                'model' => $model ?: $this->model,
                'messages' => $messages,
                'max_tokens' => $this->maxTokens,
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Провайдер вернул ошибку '.$response->status().': '.$response->body()
            );
        }

        // Некоторые провайдеры отвечают кодом 200 и телом с ошибкой внутри.
        if ($error = $response->json('error.message')) {
            throw new RuntimeException('Провайдер сообщил об ошибке: '.$error);
        }

        $content = trim((string) $response->json('choices.0.message.content'));

        // Рассуждающие модели тратят лимит на размышления и могут вернуть
        // пустой content. Тогда берём сами рассуждения, иначе пользователь
        // получил бы пустое сообщение.
        if ($content === '') {
            $content = trim((string) $response->json('choices.0.message.reasoning'));
        }

        if ($content === '') {
            throw new RuntimeException('Провайдер вернул пустой ответ.');
        }

        return new AiResponse(
            content: $content,
            model: $response->json('model') ?? $model ?? $this->model,
        );
    }
}
