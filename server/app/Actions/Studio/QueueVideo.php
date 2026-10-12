<?php

namespace App\Actions\Studio;

use App\Models\Generation;
use App\Models\User;
use App\Services\Studio\GenerationFailed;
use App\Services\Studio\PromptTranslator;
use App\Services\Studio\Thumbnailer;
use App\Services\Studio\VideoRequest;
use App\Services\Studio\VideoStudio;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Ставит ролик в работу.
 *
 * Ролик считается минутами, поэтому запись появляется сразу со
 * состоянием «готовится», а результат забирается потом — страницей
 * или фоновой командой, если человек её закрыл.
 */
class QueueVideo
{
    public function __construct(
        private readonly VideoStudio $studio,
        private readonly PromptTranslator $translator,
        private readonly Thumbnailer $thumbnailer,
    ) {
    }

    /**
     * Ролик по описанию.
     *
     * @param  array<string, mixed>  $input
     */
    public function fromText(User $user, array $input): Generation
    {
        $modelKey = $this->validModel($input['model'] ?? null);
        $model = config("studio.video.models.{$modelKey}");

        $generation = $this->record($user, $model, [
            'operation' => Generation::OP_VIDEO,
            'model_key' => $modelKey,
            'prompt' => $input['prompt'],
            'aspect_ratio' => $this->oneOf(
                $input['aspect_ratio'] ?? null,
                $model['aspect_ratios'],
                $model['default_aspect_ratio'],
            ),
        ], $input);

        return $this->start($generation, fn () => new VideoRequest(
            providerModel: $model['provider_model'],
            // Описание уходит на английском: на нём модели и обучены.
            prompt: $this->translator->translate($generation->prompt),
            duration: $generation->duration,
            resolution: $generation->resolution,
            aspectRatio: $generation->aspect_ratio,
        ));
    }

    /**
     * Оживление картинки.
     *
     * Исходник сохраняем у себя: по нему ролик можно снять заново, и
     * он же служит обложкой в галерее.
     *
     * @param  string  $image  содержимое картинки
     * @param  array<string, mixed>  $input
     */
    public function fromImage(User $user, string $image, string $mime, array $input): Generation
    {
        $modelKey = $this->validModel($input['model'] ?? null);
        $model = config("studio.video.models.{$modelKey}");
        $disk = config('studio.disk');

        $sourcePath = sprintf(
            'studio/u%d/%s-source.%s',
            $user->id,
            Str::uuid(),
            $this->extension($mime),
        );

        Storage::disk($disk)->put($sourcePath, $image);

        $thumbnail = $this->thumbnailer->make($image);
        $thumbnailPath = null;

        if ($thumbnail) {
            $thumbnailPath = sprintf('studio/u%d/%s-small.png', $user->id, Str::uuid());

            Storage::disk($disk)->put($thumbnailPath, $thumbnail);
        }

        $generation = $this->record($user, $model, [
            'operation' => Generation::OP_ANIMATE,
            'model_key' => $modelKey,
            'prompt' => filled($input['prompt'] ?? null)
                ? $input['prompt']
                : config('studio.video.default_motion'),
            'disk' => $disk,
            'source_path' => $sourcePath,
            'thumbnail_path' => $thumbnailPath,
        ], $input);

        return $this->start($generation, fn () => new VideoRequest(
            providerModel: $model['provider_model_image'],
            prompt: $this->translator->translate($generation->prompt),
            duration: $generation->duration,
            resolution: $generation->resolution,
            image: $image,
            imageMime: $mime,
        ));
    }

    /**
     * Снимает оживление заново по сохранённой картинке.
     */
    public function again(User $user, Generation $source): ?Generation
    {
        $input = [
            'model' => $source->model_key,
            'prompt' => $source->prompt,
            'duration' => $source->duration,
            'resolution' => $source->resolution,
            'aspect_ratio' => $source->aspect_ratio,
        ];

        if ($source->operation !== Generation::OP_ANIMATE) {
            return $this->fromText($user, $input);
        }

        $disk = Storage::disk($source->disk ?? config('studio.disk'));

        if (! $source->source_path || ! $disk->exists($source->source_path)) {
            return null;
        }

        return $this->fromImage(
            $user,
            $disk->get($source->source_path),
            $disk->mimeType($source->source_path) ?: 'image/png',
            $input,
        );
    }

    /**
     * Создаёт запись ролика.
     *
     * @param  array<string, mixed>  $model
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $input
     */
    private function record(User $user, array $model, array $attributes, array $input): Generation
    {
        return Generation::create([
            'user_id' => $user->id,
            'kind' => Generation::KIND_VIDEO,
            'status' => Generation::STATUS_PENDING,
            // Видео в общую ленту не попадает.
            'is_public' => false,
            'duration' => (int) $this->oneOf(
                isset($input['duration']) ? (int) $input['duration'] : null,
                $model['durations'],
                $model['default_duration'],
            ),
            'resolution' => $this->oneOf(
                $input['resolution'] ?? null,
                $model['resolutions'],
                $model['default_resolution'],
            ),
            ...$attributes,
        ]);
    }

    /**
     * Отправляет задачу провайдеру и запоминает её номер.
     *
     * @param  callable(): VideoRequest  $request
     */
    private function start(Generation $generation, callable $request): Generation
    {
        try {
            $job = $this->studio->queue($request());
        } catch (GenerationFailed $exception) {
            Log::warning('Студия: ролик не ушёл в работу', [
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
            'download_url' => $job->downloadUrl,
            'expected_ms' => $job->expectedMs,
        ])->save();

        return $generation;
    }

    /**
     * Ключ модели из нашего списка: чужой утёк бы в интерфейс.
     */
    private function validModel(?string $key): string
    {
        return $key && config("studio.video.models.{$key}")
            ? $key
            : config('studio.video.default_model');
    }

    /**
     * Значение из разрешённого набора или значение по умолчанию.
     *
     * Провайдер отвергает запрос с длиной или разрешением, которых
     * у модели нет, а человек за него уже ждал.
     */
    private function oneOf(mixed $value, array $allowed, mixed $default): mixed
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function extension(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'png',
        };
    }
}
