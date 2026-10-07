<?php

namespace App\Actions\Characters;

use App\Models\Character;
use App\Models\Generation;
use App\Models\User;
use App\Services\Files\DocumentText;
use App\Services\Studio\Thumbnailer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Создаёт персонажа или сохраняет изменения в нём.
 *
 * Одно действие на оба случая: поля и правила у создания и правки
 * одинаковые, расходится только то, есть ли уже запись.
 */
class SaveCharacter
{
    private const DISK = 'local';

    public function __construct(
        private readonly Thumbnailer $thumbnailer,
        private readonly DocumentText $documents,
    ) {
    }

    /**
     * @param  array<string, mixed>  $input  проверенные поля формы
     */
    public function handle(
        User $author,
        ?Character $character,
        array $input,
        ?UploadedFile $avatar = null,
        ?UploadedFile $context = null,
    ): Character {
        $character ??= new Character(['user_id' => $author->id]);

        $modelKey = $input['model'];

        $character->fill([
            'name' => trim($input['name']),
            'description' => $this->text($input['description'] ?? null),
            'tags' => $this->tags($input['tags'] ?? []),
            'intro' => $this->text($input['intro'] ?? null),
            'instructions' => trim($input['instructions']),
            'system_prompt' => $this->text($input['system_prompt'] ?? null),
            'memories' => $this->memories($input['memories'] ?? []),
            'model_key' => $modelKey,
            'temperature' => $input['temperature'] ?? null,
            // Персонаж на закрытой для каталога модели остаётся личным,
            // что бы ни пришло из формы.
            'is_public' => (bool) ($input['is_public'] ?? false)
                && Character::modelAllowsPublishing($modelKey),
        ]);

        // Номер нужен для папки с файлами, поэтому сначала сохраняем.
        $character->save();

        $this->applyAvatar($author, $character, $input, $avatar);
        $this->applyContext($character, $input, $context);

        $character->save();

        return $character;
    }

    /**
     * Аватар: загруженный файл, работа из Студии или ничего.
     *
     * @param  array<string, mixed>  $input
     */
    private function applyAvatar(
        User $author,
        Character $character,
        array $input,
        ?UploadedFile $avatar,
    ): void {
        $contents = null;
        $extension = 'png';

        if ($avatar) {
            $contents = $avatar->get();
            $extension = $avatar->extension() ?: 'png';
        } elseif (! empty($input['avatar_generation_id'])) {
            // Берём только свою готовую картинку: чужую работу по
            // номеру подставить нельзя.
            $work = Generation::query()
                ->where('user_id', $author->id)
                ->where('kind', Generation::KIND_IMAGE)
                ->where('status', Generation::STATUS_READY)
                ->whereNotNull('path')
                ->whereKey($input['avatar_generation_id'])
                ->first();

            if ($work) {
                $contents = Storage::disk($work->disk)->get($work->path);
                $extension = pathinfo($work->path, PATHINFO_EXTENSION) ?: 'png';
            }
        }

        if ($contents === null) {
            if (! empty($input['remove_avatar'])) {
                $this->forgetAvatar($character);
            }

            return;
        }

        // Аватар показывается небольшим кружком: хранить картинку в
        // полном размере незачем.
        $small = $this->thumbnailer->make($contents);

        if ($small !== null) {
            $contents = $small;
            $extension = 'png';
        }

        $this->forgetAvatar($character);

        $path = sprintf('characters/%d/%s.%s', $character->id, Str::uuid(), $extension);

        Storage::disk(self::DISK)->put($path, $contents);

        $character->avatar_disk = self::DISK;
        $character->avatar_path = $path;
    }

    private function forgetAvatar(Character $character): void
    {
        if ($character->avatar_path) {
            Storage::disk($character->avatar_disk ?? self::DISK)
                ->delete($character->avatar_path);
        }

        $character->avatar_disk = null;
        $character->avatar_path = null;
    }

    /**
     * Документ со сведениями.
     *
     * Сам файл не храним: модели нужен только его текст.
     *
     * @param  array<string, mixed>  $input
     */
    private function applyContext(
        Character $character,
        array $input,
        ?UploadedFile $context,
    ): void {
        if ($context) {
            $character->context_name = $context->getClientOriginalName();
            $character->context_text = $this->documents->from(
                $context,
                (int) config('characters.limits.context_chars'),
            );

            return;
        }

        if (! empty($input['remove_context'])) {
            $character->context_name = null;
            $character->context_text = null;
        }
    }

    private function text(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    /**
     * Теги без повторов и пустых значений.
     *
     * @param  array<int, mixed>  $tags
     * @return array<int, string>
     */
    private function tags(array $tags): array
    {
        return collect($tags)
            ->map(fn ($tag) => trim((string) $tag))
            ->filter()
            ->unique(fn (string $tag) => mb_strtolower($tag))
            ->take((int) config('characters.limits.tags'))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $memories
     * @return array<int, string>
     */
    private function memories(array $memories): array
    {
        return collect($memories)
            ->map(fn ($memory) => trim((string) $memory))
            ->filter()
            ->take((int) config('characters.limits.memories'))
            ->values()
            ->all();
    }
}
