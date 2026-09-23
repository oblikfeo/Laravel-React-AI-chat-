<?php

namespace App\Actions\Chat;

use App\Models\Attachment;
use App\Models\Message;
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

    public function handle(Message $message, UploadedFile $file): Attachment
    {
        $path = $file->store("attachments/{$message->chat_id}", 'local');

        return $message->attachments()->create([
            'disk' => 'local',
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'extracted_text' => $this->textOf($file),
        ]);
    }

    /**
     * Текст документа, если его удаётся прочитать.
     *
     * Картинки сюда не попадают: их понимает сама модель.
     */
    private function textOf(UploadedFile $file): ?string
    {
        $mime = (string) $file->getMimeType();

        if (str_starts_with($mime, 'image/')) {
            return null;
        }

        $text = match (true) {
            $mime === 'application/pdf' => $this->fromPdf($file),
            str_starts_with($mime, 'text/'),
            in_array($mime, ['application/json', 'application/xml'], true) => $file->get(),
            default => null,
        };

        if ($text === null) {
            return null;
        }

        // Управляющие символы ломают разбор ответа на стороне провайдера.
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $text);

        return mb_substr(trim($text), 0, self::TEXT_LIMIT) ?: null;
    }

    /**
     * Текст из PDF.
     *
     * Читаем несжатые текстовые блоки: для документов, собранных из
     * текста, этого достаточно. Отсканированные страницы — это
     * картинки, там нужен распознаватель, он появится отдельно.
     */
    private function fromPdf(UploadedFile $file): ?string
    {
        $raw = $file->get();
        $chunks = [];

        if (preg_match_all('/\((?:[^()\\\\]|\\\\.)*\)/s', $raw, $matches)) {
            foreach ($matches[0] as $chunk) {
                $chunks[] = stripcslashes(substr($chunk, 1, -1));
            }
        }

        $text = trim(preg_replace('/\s+/u', ' ', implode(' ', $chunks)));

        return $text !== '' ? $text : null;
    }
}
