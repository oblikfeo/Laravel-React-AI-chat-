<?php

namespace App\Actions\Studio;

use App\Models\Generation;
use App\Models\Guest;
use App\Models\User;
use App\Services\Studio\AudioStudio;
use App\Services\Studio\EffectRequest;
use App\Services\Studio\GenerationFailed;
use App\Services\Studio\MusicRequest;
use App\Services\Studio\PromptTranslator;
use Illuminate\Support\Facades\Log;

/**
 * Ставит долгую звуковую задачу в работу.
 *
 * Музыка и эффекты считаются дольше, чем человек готов смотреть на
 * крутилку, поэтому запись появляется сразу со состоянием «готовится»,
 * а результат забирается потом.
 */
class QueueAudio
{
    public function __construct(
        private readonly AudioStudio $studio,
        private readonly PromptTranslator $translator,
    ) {
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function music(?User $user, ?Guest $guest, array $input): Generation
    {
        $modelKey = $this->validModel('music', $input['model'] ?? null);
        $model = config("studio.music.models.{$modelKey}");

        $generation = $this->record($user, $guest, [
            'operation' => Generation::OP_MUSIC,
            'model_key' => $modelKey,
            'prompt' => $input['prompt'],
            'lyrics' => $input['lyrics'] ?? null,
            'duration' => $input['duration'] ?? ($model['default_duration'] ?? null),
        ]);

        return $this->start($generation, fn () => $this->studio->queueMusic(new MusicRequest(
            providerModel: $model['provider_model'],
            // Описание уходит на английском: на нём модели и обучены.
            prompt: $this->translator->translate($generation->prompt),
            lyrics: $generation->lyrics,
            duration: $generation->duration,
            instrumental: (bool) ($input['instrumental'] ?? false),
        )));
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function effect(?User $user, ?Guest $guest, array $input): Generation
    {
        $modelKey = $this->validModel('effects', $input['model'] ?? null);
        $model = config("studio.effects.models.{$modelKey}");

        $generation = $this->record($user, $guest, [
            'operation' => Generation::OP_EFFECT,
            'model_key' => $modelKey,
            'prompt' => $input['prompt'],
            'duration' => $input['duration'] ?? ($model['default_duration'] ?? null),
        ]);

        return $this->start($generation, fn () => $this->studio->queueEffect(new EffectRequest(
            providerModel: $model['provider_model'],
            prompt: $this->translator->translate($generation->prompt),
            duration: $generation->duration,
            loop: (bool) ($model['loop'] ?? false),
        )));
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function voiceChange(
        ?User $user,
        ?Guest $guest,
        string $recording,
        string $filename,
        array $input,
    ): Generation {
        $generation = $this->record($user, $guest, [
            'operation' => Generation::OP_VOICE_CHANGE,
            'model_key' => 'voice_changer',
            // В галерее подписью служит выбранный голос.
            'prompt' => config('studio.voices.'.($input['voice'] ?? config('studio.default_voice')).'.label')
                ?? 'Voice change',
        ]);

        return $this->start($generation, fn () => $this->studio->queueVoiceChange(
            recording: $recording,
            filename: $filename,
            voice: config('studio.voices.'.($input['voice'] ?? config('studio.default_voice')).'.provider'),
            removeNoise: (bool) ($input['remove_noise'] ?? false),
        ));
    }

    /**
     * Создаёт запись работы.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function record(?User $user, ?Guest $guest, array $attributes): Generation
    {
        return Generation::create([
            'user_id' => $user?->id,
            'guest_id' => $user ? null : $guest?->id,
            'kind' => Generation::KIND_AUDIO,
            'status' => Generation::STATUS_PENDING,
            // Звук в общую ленту не попадает.
            'is_public' => false,
            ...$attributes,
        ]);
    }

    /**
     * Отправляет задачу провайдеру и запоминает её номер.
     */
    private function start(Generation $generation, callable $queue): Generation
    {
        try {
            $job = $queue();
        } catch (GenerationFailed $exception) {
            Log::warning('Студия: звук не ушёл в работу', [
                'generation_id' => $generation->id,
                'operation' => $generation->operation,
                'message' => $exception->getMessage(),
            ]);

            $generation->forceFill([
                'status' => Generation::STATUS_FAILED,
                'failure_reason' => $exception->getMessage(),
            ])->save();

            return $generation;
        }

        $generation->forceFill([
            'status' => Generation::STATUS_QUEUED,
            'queue_id' => $job->id,
            'expected_ms' => $job->expectedMs,
        ])->save();

        return $generation;
    }

    /**
     * Ключ модели из нашего списка.
     *
     * Чужой ключ сохранился бы в работе и утёк в интерфейс названием
     * модели провайдера.
     */
    private function validModel(string $group, ?string $key): string
    {
        if ($key && config("studio.{$group}.models.{$key}")) {
            return $key;
        }

        $default = config("studio.{$group}.default_model");

        return config("studio.{$group}.models.{$default}")
            ? $default
            : array_key_first(config("studio.{$group}.models"));
    }
}
