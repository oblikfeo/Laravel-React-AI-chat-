<?php

namespace App\Services\Ai;

interface AiChatProvider
{
    /**
     * Отправляет историю диалога модели и возвращает ответ.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function complete(array $messages): AiResponse;

    /**
     * Готов ли провайдер к реальным запросам (задан ключ).
     */
    public function isConfigured(): bool;
}
