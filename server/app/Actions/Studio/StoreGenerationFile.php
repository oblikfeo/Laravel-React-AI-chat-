<?php

namespace App\Actions\Studio;

use App\Models\Generation;
use App\Services\Studio\Thumbnailer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Кладёт готовый файл в хранилище и отмечает работу готовой.
 *
 * Файлы лежат вне публичной папки и отдаются через контроллер с
 * проверкой владельца, поэтому чужую работу по ссылке не открыть.
 */
class StoreGenerationFile
{
    public function __construct(private readonly Thumbnailer $thumbnailer)
    {
    }

    public function store(
        Generation $generation,
        string $contents,
        string $extension,
        string $mime,
    ): Generation {
        $disk = config('studio.disk');

        $owner = $generation->user_id
            ? "u{$generation->user_id}"
            : "g{$generation->guest_id}";

        $path = sprintf('studio/%s/%s.%s', $owner, Str::uuid(), $extension);

        Storage::disk($disk)->put($path, $contents);

        // Уменьшенная копия для ленты: полные файлы весят мегабайты.
        $thumbnail = str_starts_with($mime, 'image/')
            ? $this->thumbnailer->make($contents)
            : null;

        $thumbnailPath = null;

        if ($thumbnail) {
            $thumbnailPath = sprintf('studio/%s/%s-small.png', $owner, Str::uuid());

            Storage::disk($disk)->put($thumbnailPath, $thumbnail);
        }

        // Настоящие размеры картинки: провайдер мог отдать не то,
        // что просили, а лента раскладывает плитки по пропорциям.
        $size = str_starts_with($mime, 'image/')
            ? @getimagesizefromstring($contents)
            : null;

        $generation->forceFill([
            'status' => Generation::STATUS_READY,
            ...($size ? ['width' => $size[0], 'height' => $size[1]] : []),
            'disk' => $disk,
            'path' => $path,
            'thumbnail_path' => $thumbnailPath,
            'mime' => $mime,
            'failure_reason' => null,
            'completed_at' => now(),
        ])->save();

        return $generation;
    }
}
