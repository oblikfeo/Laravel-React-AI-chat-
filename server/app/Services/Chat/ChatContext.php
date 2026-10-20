<?php

namespace App\Services\Chat;

use App\Models\Attachment;
use App\Models\Chat;
use App\Models\Message;
use App\Services\Characters\CharacterPrompt;

/**
 * История чата в том виде, в каком её получает текстовая модель:
 * системный запрос и последние реплики.
 */
class ChatContext
{
    public function __construct(private readonly CharacterPrompt $characterPrompt) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function for(Chat $chat): array
    {
        $history = $chat->messages()
            ->with(['attachments', 'generation'])
            // Отметки о смене модели — для человека, не для модели.
            ->whereIn('role', [Message::ROLE_USER, Message::ROLE_ASSISTANT])
            ->latest('id')
            ->limit(config('ai.context_messages'))
            ->get()
            ->reverse()
            ->values();

        // Ответы картинкой в историю не попадают: увидев образец, модель
        // начинает «рисовать» текстом. О нарисованном говорим в
        // системном запросе.
        $pictures = $history
            ->filter(fn (Message $message) => $message->generation)
            ->map(fn (Message $message) => '«'.$message->generation->prompt.'»')
            ->implode(', ');

        $turns = $this->merged($history
            ->reject(fn (Message $message) => $message->generation)
            ->map(fn (Message $message) => $this->formatMessage($message))
            ->values()
            ->all());

        $system = $this->systemPrompt($chat);

        if ($pictures !== '') {
            $system .= "\n\nPictures the app has already shown the user in this conversation: {$pictures}.";
        }

        return array_merge([['role' => 'system', 'content' => $system]], $turns);
    }

    /** Есть ли в чате картинки, которые модели нужно увидеть. */
    public function hasImages(Chat $chat): bool
    {
        return $chat->messages()
            ->whereHas('attachments', fn ($q) => $q->where('mime', 'like', 'image/%'))
            ->exists();
    }

    /**
     * Кем быть модели в этом чате.
     *
     * В обычном чате это наш помощник, в диалоге с персонажем —
     * сам персонаж со всем, что о нём написал автор.
     */
    private function systemPrompt(Chat $chat): string
    {
        $role = $chat->character
            ? $this->characterPrompt->for($chat->character)
            : config('ai.system_prompt');

        return $role."\n\n".config('ai.images_note');
    }

    /**
     * Готовит одно сообщение к отправке.
     *
     * Текст документа вставляется прямо в сообщение: модель не умеет
     * открывать файлы, она работает только с тем, что ей прислали.
     * Картинки уходят отдельными частями в формате провайдера.
     *
     * @return array<string, mixed>
     */
    private function formatMessage(Message $message): array
    {
        $attachments = $message->attachments;

        if ($attachments->isEmpty()) {
            return ['role' => $message->role, 'content' => $message->content];
        }

        $text = $message->content;

        foreach ($attachments->where('extracted_text', '!=', null) as $document) {
            $text .= "\n\n--- {$document->name} ---\n{$document->extracted_text}";
        }

        $images = $attachments->filter(fn (Attachment $a) => $a->isImage());

        if ($images->isEmpty()) {
            return ['role' => $message->role, 'content' => $text];
        }

        $parts = [['type' => 'text', 'text' => $text]];

        foreach ($images as $image) {
            $parts[] = [
                'type' => 'image_url',
                'image_url' => ['url' => $image->asDataUrl()],
            ];
        }

        return ['role' => $message->role, 'content' => $parts];
    }

    /**
     * Соседние реплики одной стороны склеиваются в одну.
     *
     * Так бывает, когда ответ картинкой выпал из истории: две реплики
     * человека подряд некоторые модели не принимают.
     *
     * @param  array<int, array<string, mixed>>  $turns
     * @return array<int, array<string, mixed>>
     */
    private function merged(array $turns): array
    {
        $result = [];

        foreach ($turns as $turn) {
            $last = array_key_last($result);

            if ($last !== null
                && $result[$last]['role'] === $turn['role']
                && is_string($result[$last]['content'])
                && is_string($turn['content'])) {
                $result[$last]['content'] .= "\n\n".$turn['content'];

                continue;
            }

            $result[] = $turn;
        }

        return $result;
    }
}
