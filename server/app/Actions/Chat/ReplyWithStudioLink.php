<?php

namespace App\Actions\Chat;

use App\Models\Chat;
use App\Models\Message;
use App\Services\Chat\ChatImages;

/**
 * Отвечает в чате карточкой «сделать это в Студии».
 *
 * Звук и видео чат не делает: ролик считается минутами, у звука
 * много своих настроек. Карточка открывает нужный раздел Студии с
 * уже заполненной формой, а для оживления — и с картинкой из чата.
 */
class ReplyWithStudioLink
{
    /**
     * @param  array{mode: string, operation: string, prompt: string}  $intent
     */
    public function handle(Chat $chat, array $intent, ChatImages $images): Message
    {
        return $chat->messages()->create([
            'role' => Message::ROLE_ASSISTANT,
            'content' => '',
            'model' => 'studio',
            'studio_link' => [
                'tab' => $intent['mode'],
                'operation' => $intent['operation'],
                'prompt' => $intent['prompt'],
                ...$images->origin(),
            ],
        ]);
    }
}
