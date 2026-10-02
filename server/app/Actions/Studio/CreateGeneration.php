<?php

namespace App\Actions\Studio;

use App\Models\Generation;
use App\Models\Guest;
use App\Models\User;
use App\Services\Studio\GenerationFailed;
use App\Services\Studio\GenerationRequest;
use App\Services\Studio\ImageGenerator;
use App\Services\Studio\PromptTranslator;
use Illuminate\Support\Facades\Log;

/**
 * Рисует по описанию.
 *
 * Запись появляется до обращения к провайдеру: если генерация не
 * удастся, человек увидит неудачную попытку с кнопкой повтора,
 * а не пустое место.
 */
class CreateGeneration
{
    public function __construct(
        private readonly ImageGenerator $generator,
        private readonly StoreGenerationFile $files,
        private readonly PromptTranslator $translator,
    ) {
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<int, Generation> по работе на каждый вариант
     */
    public function handle(?User $user, ?Guest $guest, array $input): array
    {
        $variants = min(
            max((int) ($input['variants'] ?? 1), 1),
            (int) config('studio.max_variants'),
        );

        $generation = Generation::create([
            'user_id' => $user?->id,
            'guest_id' => $user ? null : $guest?->id,
            'kind' => Generation::KIND_IMAGE,
            'operation' => Generation::OP_GENERATE,
            'model_key' => $input['model'],
            'status' => Generation::STATUS_PENDING,
            'prompt' => $input['prompt'],
            'negative_prompt' => $input['negative_prompt'] ?? null,
            'aspect_ratio' => $input['aspect_ratio'],
            'style' => $input['style'] ?? null,
            // Зерно запоминаем всегда: без него повторная генерация
            // дала бы совсем другую картинку.
            'seed' => $input['seed'] ?? random_int(1, config('studio.max_seed')),
            'variants' => $variants,
        ]);

        return $this->run($generation);
    }

    /**
     * Повторяет генерацию по сохранённым условиям.
     *
     * @return array<int, Generation>
     */
    public function run(Generation $generation): array
    {
        $size = config("studio.aspect_ratios.{$generation->aspect_ratio}");
        $model = config("studio.models.{$generation->model_key}");
        $variants = max((int) $generation->variants, 1);

        try {
            $images = $this->generator->generate(new GenerationRequest(
                providerModel: $model['provider_model'],
                // Описание уходит на английском: на нём модели
                // рисования и обучены.
                prompt: $this->translator->translate($generation->prompt),
                negativePrompt: $generation->negative_prompt
                    ? $this->translator->translate($generation->negative_prompt)
                    : null,
                aspectRatio: $generation->aspect_ratio,
                seed: $generation->seed,
                width: $size['width'] ?? 1024,
                height: $size['height'] ?? 1024,
                variants: $variants,
                stylePreset: config("studio.styles.{$generation->style}"),
                supportsAspectRatio: (bool) ($model['aspect_ratio'] ?? false),
                divisor: (int) ($model['divisor'] ?? 8),
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

            return [$generation];
        }

        // Первый вариант занимает уже созданную запись, остальные
        // получают свои: в ленте это отдельные работы.
        $created = [];

        foreach (array_values($images) as $index => $image) {
            $target = $index === 0
                ? $generation
                : $generation->replicate(['status', 'disk', 'path', 'completed_at']);

            $this->files->store($target, $image->contents, $image->extension(), $image->mime);

            $target->forceFill([
                'width' => $image->width,
                'height' => $image->height,
            ])->save();

            $created[] = $target;
        }

        return $created;
    }
}
