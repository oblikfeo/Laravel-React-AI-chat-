<?php

namespace App\Actions\Studio;

use App\Models\Generation;
use App\Models\Guest;
use App\Models\User;
use App\Services\Studio\GenerationFailed;
use App\Services\Studio\ImageGenerator;
use App\Services\Studio\SpeechRequest;
use Illuminate\Support\Facades\Log;

/**
 * Озвучивает текст.
 *
 * Запись хранится рядом с картинками: для ленты это такая же работа,
 * отличается только видом файла.
 */
class CreateSpeech
{
    public function __construct(
        private readonly ImageGenerator $generator,
        private readonly StoreGenerationFile $files,
    ) {
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(?User $user, ?Guest $guest, array $input): Generation
    {
        $modelKey = $input['model'] ?? config('studio.speech.default_model');

        // Ключ должен быть из нашего списка: иначе работа сохранится с
        // названием модели провайдера и оно утечёт в интерфейс.
        if (! config("studio.speech.models.{$modelKey}")) {
            $modelKey = array_key_first(config('studio.speech.models'));
        }

        $generation = Generation::create([
            'user_id' => $user?->id,
            'guest_id' => $user ? null : $guest?->id,
            'kind' => Generation::KIND_AUDIO,
            'operation' => Generation::OP_SPEECH,
            'model_key' => $modelKey,
            'status' => Generation::STATUS_PENDING,
            'prompt' => $input['text'],
        ]);

        try {
            $audio = $this->generator->speech(new SpeechRequest(
                text: $input['text'],
                providerModel: config("studio.speech.models.{$modelKey}.provider_model"),
                voice: $input['voice'] ?? null,
                speed: (float) ($input['speed'] ?? 1.0),
            ));
        } catch (GenerationFailed $exception) {
            Log::warning('Озвучка не удалась', [
                'generation_id' => $generation->id,
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
            $audio->contents,
            $audio->extension(),
            $audio->mime,
        );
    }
}
