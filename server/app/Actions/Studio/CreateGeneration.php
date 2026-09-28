<?php

namespace App\Actions\Studio;

use App\Models\Generation;
use App\Models\Guest;
use App\Models\User;
use App\Services\Studio\GenerationFailed;
use App\Services\Studio\GenerationRequest;
use App\Services\Studio\ImageGenerator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Создаёт работу в Студии.
 *
 * Запись появляется до обращения к провайдеру: если генерация не
 * удастся, человек увидит неудачную попытку с кнопкой повтора,
 * а не пустое место.
 */
class CreateGeneration
{
    public function __construct(private readonly ImageGenerator $generator)
    {
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(?User $user, ?Guest $guest, array $input): Generation
    {
        $generation = Generation::create([
            'user_id' => $user?->id,
            'guest_id' => $user ? null : $guest?->id,
            'kind' => Generation::KIND_IMAGE,
            'model_key' => $input['model'],
            'status' => Generation::STATUS_PENDING,
            'prompt' => $input['prompt'],
            'negative_prompt' => $input['negative_prompt'] ?? null,
            'aspect_ratio' => $input['aspect_ratio'],
            'style' => $input['style'] ?? null,
            // Зерно запоминаем всегда: без него повторная генерация
            // дала бы совсем другую картинку.
            'seed' => $input['seed'] ?? random_int(1, 2_147_483_647),
        ]);

        return $this->run($generation);
    }

    /** Повторяет генерацию по сохранённым условиям. */
    public function run(Generation $generation): Generation
    {
        $size = config("studio.aspect_ratios.{$generation->aspect_ratio}");

        try {
            $image = $this->generator->generate(new GenerationRequest(
                providerModel: config("studio.models.{$generation->model_key}.provider_model"),
                prompt: $this->fullPrompt($generation),
                negativePrompt: $generation->negative_prompt,
                aspectRatio: $generation->aspect_ratio,
                seed: $generation->seed,
                width: $size['width'] ?? 1024,
                height: $size['height'] ?? 1024,
            ));
        } catch (GenerationFailed $exception) {
            Log::warning('Генерация изображения не удалась', [
                'generation_id' => $generation->id,
                'message' => $exception->getMessage(),
            ]);

            $generation->forceFill([
                'status' => Generation::STATUS_FAILED,
                'failure_reason' => $exception->getMessage(),
            ])->save();

            return $generation;
        }

        $disk = config('studio.disk');
        $path = sprintf(
            'studio/%s/%s.%s',
            $generation->user_id ? "u{$generation->user_id}" : "g{$generation->guest_id}",
            Str::uuid(),
            $image->extension(),
        );

        Storage::disk($disk)->put($path, $image->contents);

        $generation->forceFill([
            'status' => Generation::STATUS_READY,
            'disk' => $disk,
            'path' => $path,
            'width' => $image->width,
            'height' => $image->height,
            'failure_reason' => null,
            'completed_at' => now(),
        ])->save();

        return $generation;
    }

    /**
     * Описание вместе со стилем.
     *
     * Человек пишет, что хочет увидеть, а стиль добавляет то, как это
     * должно выглядеть: держать оформление в голове он не обязан.
     */
    private function fullPrompt(Generation $generation): string
    {
        $suffix = config("studio.styles.{$generation->style}.suffix");

        return $suffix
            ? $generation->prompt.', '.$suffix
            : $generation->prompt;
    }
}
