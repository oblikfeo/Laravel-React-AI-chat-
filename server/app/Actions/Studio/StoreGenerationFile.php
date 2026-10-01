<?php

namespace App\Actions\Studio;

use App\Models\Generation;
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

        $generation->forceFill([
            'status' => Generation::STATUS_READY,
            'disk' => $disk,
            'path' => $path,
            'mime' => $mime,
            'failure_reason' => null,
            'completed_at' => now(),
        ])->save();

        return $generation;
    }
}
