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

        $turns = $this->merged($this->fitted($history
            ->reject(fn (Message $message) => $message->generation)
            ->values()
            ->all()));

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
     * Реплики, которые помещаются в предел истории.
     *
     * Идём от новых к старым: последняя реплика уходит всегда, старые —
     * пока хватает места. Картинки тоже только самые свежие; на месте
     * остальных — пометка, что картинка была.
     *
     * @param  array<int, Message>  $messages  от старых к новым
     * @return array<int, array<string, mixed>>
     */
    private function fitted(array $messages): array
    {
        $room = (int) config('ai.context_chars');
        $images = (int) config('ai.context_images');
        $turns = [];

        foreach (array_reverse($messages) as $message) {
            $turn = $this->formatMessage($message, $room, $images);
            $length = mb_strlen($this->textOf($turn));

            if ($turns && $length > $room) {
                break;
            }

            $room -= $length;
            // Первая часть составного сообщения — текст, остальные — картинки.
            if (is_array($turn['content'])) {
                $images -= count($turn['content']) - 1;
            }

            array_unshift($turns, $turn);
        }

        return $turns;
    }

    /** @param  array<string, mixed>  $turn */
    private function textOf(array $turn): string
    {
        return is_string($turn['content'])
            ? $turn['content']
            : (string) ($turn['content'][0]['text'] ?? '');
    }

    /**
     * Готовит одно сообщение к отправке.
     *
     * Текст документа вставляется прямо в сообщение: модель не умеет
     * открывать файлы, она работает только с тем, что ей прислали.
     * Длинный документ обрезается по оставшемуся месту. Картинки
     * уходят отдельными частями в формате провайдера.
     *
     * @return array<string, mixed>
     */
    private function formatMessage(Message $message, int $room, int $images): array
    {
        $attachments = $message->attachments;

        if ($attachments->isEmpty()) {
            return ['role' => $message->role, 'content' => $message->content];
        }

        $text = $message->content;

        foreach ($attachments->where('extracted_text', '!=', null) as $document) {
            $left = $room - mb_strlen($text) - mb_strlen($document->name) - 10;

            if ($left < 200) {
                $text .= "\n\n({$document->name} was attached here; it is too long to include again.)";

                continue;
            }

            $content = mb_strlen($document->extracted_text) > $left
                ? mb_substr($document->extracted_text, 0, $left).'…'
                : $document->extracted_text;

            $text .= "\n\n--- {$document->name} ---\n{$content}";
        }

        $all = $attachments->filter(fn (Attachment $a) => $a->isImage());
        $images = $all->take(max(0, $images));

        if ($all->count() > $images->count()) {
            $text .= "\n\n(".($all->count() - $images->count()).' more image(s) were attached here.)';
        }

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
