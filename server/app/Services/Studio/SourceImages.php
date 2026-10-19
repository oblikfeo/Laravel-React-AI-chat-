<?php

namespace App\Services\Studio;

use App\Models\Generation;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Исходные картинки для инструментов Студии.
 *
 * Картинка приходит файлом с диска или номером готовой работы. Работу
 * можно взять свою или показанную в общей ленте; скрытая чужая работа
 * недоступна, как и при открытии файла.
 */
class SourceImages
{
    /**
     * Содержимое картинок по номерам работ и загруженным файлам.
     *
     * @param  array<int, int>  $ids
     * @param  array<int, UploadedFile>  $files
     * @return array<int, string>
     */
    public function collect(?User $user, ?Guest $guest, array $ids, array $files): array
    {
        $sources = [];

        if ($ids = array_filter($ids)) {
            $generations = Generation::query()
                ->whereKey($ids)
                ->whereNotNull('path')
                ->where(fn ($query) => $query
                    ->where(fn ($own) => $own->ownedBy($user, $guest))
                    ->orWhere(fn ($shared) => $shared->inFeed()))
                ->get();

            foreach ($generations as $generation) {
                $sources[] = Storage::disk($generation->disk)->get($generation->path);
            }
        }

        foreach ($files as $file) {
            $sources[] = $file->get();
        }

        return array_values(array_filter($sources));
    }

    /**
     * Одна картинка своей работы или файл: содержимое и тип.
     *
     * @return array{0: string, 1: string}|null
     */
    public function one(User $user, ?int $id, ?UploadedFile $file): ?array
    {
        if ($file) {
            return [$file->get(), $file->getMimeType() ?: 'image/png'];
        }

        $generation = $id
            ? Generation::query()
                ->whereKey($id)
                ->where('user_id', $user->id)
                ->where('kind', Generation::KIND_IMAGE)
                ->whereNotNull('path')
                ->first()
            : null;

        return $generation
            ? [Storage::disk($generation->disk)->get($generation->path), $generation->mime ?: 'image/png']
            : null;
    }
}
