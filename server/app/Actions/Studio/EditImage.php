<?php

namespace App\Actions\Studio;

use App\Models\Generation;
use App\Models\Guest;
use App\Models\User;
use App\Services\Studio\EditRequest;
use App\Services\Studio\GeneratedImage;
use App\Services\Studio\GenerationFailed;
use App\Services\Studio\ImageGenerator;
use App\Services\Studio\PromptTranslator;
use Illuminate\Support\Facades\Log;

/**
 * Инструменты правки готового изображения.
 *
 * Все четыре работают одинаково: берут исходник, зовут провайдера и
 * сохраняют результат новой работой. Исходник при этом остаётся —
 * человек должен иметь возможность вернуться к нему.
 */
class EditImage
{
    public function __construct(
        private readonly ImageGenerator $generator,
        private readonly StoreGenerationFile $files,
        private readonly PromptTranslator $translator,
    ) {
    }

    /**
     * @param  array<int, string>  $sources содержимое исходных файлов
     * @param  array<string, mixed>  $input
     */
    public function handle(?User $user, ?Guest $guest, array $sources, array $input): Generation
    {
        $operation = $input['operation'];

        $generation = Generation::create([
            'user_id' => $user?->id,
            'guest_id' => $user ? null : $guest?->id,
            'kind' => Generation::KIND_IMAGE,
            'operation' => $operation,
            'source_generation_id' => $input['source_ids'][0] ?? null,
            'model_key' => $input['model'] ?? 'standard',
            'status' => Generation::STATUS_PENDING,
            'is_public' => (bool) ($input['is_public'] ?? true),
            'prompt' => $input['prompt'] ?? $this->titleFor($operation),
            'aspect_ratio' => $input['aspect_ratio'] ?? '1:1',
        ]);

        try {
            $image = $this->apply($operation, $sources, $input);
        } catch (GenerationFailed $exception) {
            Log::warning('Правка изображения не удалась', [
                'generation_id' => $generation->id,
                'operation' => $operation,
                'message' => $exception->getMessage(),
            ]);

            $generation->forceFill([
                'status' => Generation::STATUS_FAILED,
                'failure_reason' => $exception->getMessage(),
            ])->save();

            return $generation;
        }

        return $this->files->store(
            $generation,
            $image->contents,
            $image->extension(),
            $image->mime,
        );
    }

    /**
     * @param  array<int, string>  $sources
     * @param  array<string, mixed>  $input
     */
    private function apply(string $operation, array $sources, array $input): GeneratedImage
    {
        $first = $sources[0] ?? throw new GenerationFailed('Нет исходного изображения.');

        // Описание уходит на английском: на нём модели и обучены.
        $prompt = isset($input['prompt'])
            ? $this->translator->translate($input['prompt'])
            : '';

        return match ($operation) {
            Generation::OP_EDIT => $this->generator->edit(new EditRequest(
                image: $first,
                prompt: $prompt,
                providerModel: config('studio.edit.model'),
                aspectRatio: $input['aspect_ratio'] ?? null,
            )),

            Generation::OP_COMBINE => $this->generator->combine(
                images: $sources,
                prompt: $prompt,
                aspectRatio: $input['aspect_ratio'] ?? null,
            ),

            Generation::OP_UPSCALE => $this->generator->upscale(
                image: $first,
                scale: (int) ($input['scale'] ?? 2),
            ),

            Generation::OP_BACKGROUND => $this->generator->removeBackground($first),

            default => throw new GenerationFailed("Неизвестная операция: {$operation}"),
        };
    }

    /**
     * Подпись работы, когда описания нет.
     *
     * У увеличения и удаления фона человек ничего не пишет, но в ленте
     * работа должна называться.
     */
    private function titleFor(string $operation): string
    {
        return match ($operation) {
            Generation::OP_UPSCALE => 'Upscaled image',
            Generation::OP_BACKGROUND => 'Background removed',
            default => 'Edited image',
        };
    }
}
