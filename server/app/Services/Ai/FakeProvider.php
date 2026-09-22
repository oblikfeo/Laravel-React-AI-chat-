<?php

namespace App\Services\Ai;

/**
 * Заглушка на время, пока не вставлен ключ провайдера.
 *
 * Позволяет проверить весь путь сообщения — от формы до отрисовки
 * диалога — без внешних запросов и без оплаты.
 */
class FakeProvider implements AiChatProvider
{
    public function isConfigured(): bool
    {
        return true;
    }

    public function complete(array $messages): AiResponse
    {
        $lastUserMessage = collect($messages)
            ->last(fn (array $message) => $message['role'] === 'user')['content'] ?? '';

        $reply = <<<TEXT
        This is a demo reply: no AI provider key is connected yet.

        Your message: "{$lastUserMessage}"

        To enable real answers, grab a free key at openrouter.ai and add it
        to your .env file as OPENROUTER_API_KEY. No restart needed.
        TEXT;

        return new AiResponse(content: $reply, model: 'demo-provider');
    }
}
