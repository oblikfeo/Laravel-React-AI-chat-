<?php

namespace App\Actions\Chat;

use App\Models\Attachment;
use App\Models\Message;
use App\Services\Files\DocumentText;
use Illuminate\Http\UploadedFile;

/**
 * Сохраняет приложенный файл и достаёт из него текст.
 *
 * Модель не умеет открывать файлы: ей отправляется содержимое.
 * Поэтому текст извлекается сразу при загрузке, а не при каждом
 * обращении к модели.
 */
class StoreAttachment
{
    /** Сколько символов документа отправляем модели. */
    private const TEXT_LIMIT = 40000;

    public function __construct(private readonly DocumentText $documents)
    {
    }

    public function handle(Message $message, UploadedFile $file): Attachment
    {
        $path = $file->store("attachments/{$message->chat_id}", 'local');

        return $message->attachments()->create([
            'disk' => 'local',
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'extracted_text' => $this->documents->from($file, self::TEXT_LIMIT),
        ]);
    }
}
