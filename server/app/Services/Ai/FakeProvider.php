<?php

namespace App\Services\Ai;

/**
 * Запасной ответ, когда обращаться к модели не к кому: ключ не задан.
 *
 * Пользователю не сообщается ни о ключе, ни о провайдере, ни о настройках:
 * боевой сервер смотрят люди, и внутренняя кухня в интерфейс не попадает.
 * Разработчик узнаёт о причине из лога, см. AiServiceProvider.
 */
class FakeProvider implements AiChatProvider
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function complete(array $messages): AiResponse
    {
        return new AiResponse(
            content: "I'm having trouble responding right now. Please try again in a moment.",
            model: null,
        );
    }
}
