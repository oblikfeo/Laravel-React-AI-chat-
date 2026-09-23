<?php

namespace App\Actions\Chat;

use App\Models\Chat;
use App\Models\Message;

/**
 * Запрашивает ответ модели на последнее сообщение чата.
 *
 * Вынесено из SendMessage, чтобы создание чата не ждало модель:
 * человек попадает в диалог сразу и видит там индикатор ожидания,
 * а не полосу загрузки на главной.
 */
class RequestReply
{
    public function __construct(private readonly SendMessage $sendMessage)
    {
    }

    /** Нужен ли ответ: последнее слово должно быть за пользователем. */
    public function isPending(Chat $chat): bool
    {
        // Отметку о смене модели пропускаем: она не меняет того,
        // чья очередь говорить.
        $last = $chat->messages()
            ->whereIn('role', [Message::ROLE_USER, Message::ROLE_ASSISTANT])
            ->latest('id')
            ->first();

        return $last !== null && $last->role === Message::ROLE_USER;
    }

    public function handle(Chat $chat): ?Message
    {
        if (! $this->isPending($chat)) {
            return null;
        }

        return $this->sendMessage->reply($chat);
    }
}
