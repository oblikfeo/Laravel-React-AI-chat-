<?php

namespace App\Services\Files;

use Illuminate\Http\UploadedFile;

/**
 * Текст из загруженного документа.
 *
 * Модель не умеет открывать файлы: ей отправляется содержимое. Нужно
 * и вложениям чата, и документу со сведениями о персонаже, поэтому
 * живёт отдельно от обоих.
 */
class DocumentText
{
    /**
     * Текст документа, если его удаётся прочитать.
     *
     * Картинки сюда не попадают: их понимает сама модель.
     */
    public function from(UploadedFile $file, int $limit): ?string
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

        return mb_substr(trim((string) $text), 0, $limit) ?: null;
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
