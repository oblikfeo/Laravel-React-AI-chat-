<?php

namespace App\Services\Studio;

/**
 * Ответ на вопрос «готово ли».
 *
 * Либо работа ещё идёт, либо пришёл готовый файл.
 */
class QueueResult
{
    private function __construct(
        public readonly bool $done,
        public readonly ?string $contents = null,
        public readonly ?string $mime = null,
        public readonly ?string $status = null,
        public readonly ?int $expectedMs = null,
    ) {
    }

    public static function pending(string $status, ?int $expectedMs = null): self
    {
        return new self(done: false, status: $status, expectedMs: $expectedMs);
    }

    public static function ready(string $contents, string $mime): self
    {
        return new self(done: true, contents: $contents, mime: $mime);
    }

    public function extension(): string
    {
        return match ($this->mime) {
            'audio/wav', 'audio/x-wav' => 'wav',
            'audio/flac', 'audio/x-flac' => 'flac',
            'audio/mp4', 'audio/x-m4a' => 'm4a',
            'audio/aac' => 'aac',
            'audio/opus', 'audio/ogg' => 'opus',
            default => 'mp3',
        };
    }
}
