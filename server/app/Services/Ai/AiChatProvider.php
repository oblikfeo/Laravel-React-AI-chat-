<?php

namespace App\Services\Ai;

interface AiChatProvider
{
    /**
     * Отправляет историю диалога модели и возвращает ответ.
     *
     * @param  array<int, array<string, mixed>>  $messages
     * @param  string|null  $model  Название модели у провайдера.
     *                              Пусто — берётся из настроек.
     * @param  array<string, mixed>  $options  Тонкие настройки ответа.
     *                              Сейчас это temperature: её задаёт
     *                              автор персонажа.
     */
    public function complete(array $messages, ?string $model = null, array $options = []): AiResponse;

    /**
     * Готов ли провайдер к реальным запросам (задан ключ).
     */
    public function isConfigured(): bool;
}
