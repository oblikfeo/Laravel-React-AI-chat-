<?php

namespace App\Services\Chat;

use App\Models\Attachment;
use App\Models\Chat;
use App\Models\Generation;
use App\Models\Message;
use Illuminate\Support\Facades\Storage;

/**
 * Картинки, с которыми можно работать в ответ на сообщение.
 *
 * Это картинки, приложенные к самому сообщению, а если их нет — та,
 * которой чат ответил прошлым сообщением: «убери фон» следующей
 * репликой относится к ней. Более ранние картинки не трогаем, иначе
 * правка задела бы то, о чём человек уже забыл.
 */
class ChatImages
{
    /** @var array<int, Attachment> */
    private array $attachments = [];

    private ?int $generationId = null;

    private function __construct() {}

    public static function for(Chat $chat, Message $question): self
    {
        $images = new self;

        $images->attachments = $question->attachments
            ->filter(fn (Attachment $attachment) => $attachment->isImage())
            ->values()
            ->all();

        if ($images->attachments) {
            return $images;
        }

        $generation = $chat->messages()
            ->with('generation')
            ->where('role', Message::ROLE_ASSISTANT)
            ->where('id', '<', $question->id)
            ->latest('id')
            ->first()
            ?->generation;

        $images->generationId = $generation?->isReady() ? $generation->id : null;

        return $images;
    }

    /** Сколько картинок приложено к самому сообщению. */
    public function attached(): int
    {
        return count($this->attachments);
    }

    public function count(): int
    {
        return $this->attachments ? count($this->attachments) : ($this->generationId ? 1 : 0);
    }

    /**
     * Содержимое картинок.
     *
     * @return array<int, string>
     */
    public function contents(): array
    {
        if ($this->attachments) {
            return array_map(
                fn (Attachment $attachment) => Storage::disk($attachment->disk)->get($attachment->path),
                $this->attachments,
            );
        }

        $generation = $this->generationId
            ? Generation::find($this->generationId)
            : null;

        return $generation ? [Storage::disk($generation->disk)->get($generation->path)] : [];
    }

    /**
     * Откуда взята первая картинка — чтобы Студия открылась с ней.
     *
     * @return array{attachment: int|null, work: int|null}
     */
    public function origin(): array
    {
        return [
            'attachment' => $this->attachments[0]->id ?? null,
            'work' => $this->attachments ? null : $this->generationId,
        ];
    }
}
