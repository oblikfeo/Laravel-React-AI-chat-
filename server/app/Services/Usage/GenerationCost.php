<?php

namespace App\Services\Usage;

use App\Models\Generation;

/**
 * Себестоимость работы Студии по ценам из config/studio.php.
 *
 * Провайдер берёт деньги по-разному: за работу, за секунду результата,
 * за символы текста или по таблице длительностей. Здесь всё сводится
 * к одной сумме в долларах — та же арифметика, по которой интерфейс
 * показывает цену до запуска.
 */
class GenerationCost
{
    /**
     * Цена запроса до запуска — по тем же настройкам, что придут в
     * форме. Чего человек не задал, берётся по умолчанию модели.
     *
     * @param  array<string, mixed>  $input
     */
    public static function estimate(string $operation, array $input): float
    {
        [$kind, $group] = match ($operation) {
            Generation::OP_VIDEO, Generation::OP_ANIMATE => [Generation::KIND_VIDEO, 'video'],
            Generation::OP_SPEECH => [Generation::KIND_AUDIO, 'speech'],
            Generation::OP_MUSIC => [Generation::KIND_AUDIO, 'music'],
            Generation::OP_EFFECT => [Generation::KIND_AUDIO, 'effects'],
            Generation::OP_VOICE_CHANGE => [Generation::KIND_AUDIO, null],
            default => [Generation::KIND_IMAGE, null],
        };

        $model = $input['model'] ?? ($group ? config("studio.{$group}.default_model") : config('studio.default_model'));
        $defaults = $group ? config("studio.{$group}.models.{$model}", []) : [];

        return self::of(new Generation([
            'kind' => $kind,
            'operation' => $operation,
            'model_key' => $operation === Generation::OP_VOICE_CHANGE ? 'voice_changer' : $model,
            'variants' => max(1, (int) ($input['variants'] ?? 1)),
            'duration' => $input['duration'] ?? $input['seconds'] ?? ($defaults['default_duration'] ?? null),
            'resolution' => $input['resolution'] ?? ($defaults['default_resolution'] ?? null),
            'prompt' => (string) ($input['text'] ?? $input['prompt'] ?? ''),
        ]), ['scale' => (int) ($input['scale'] ?? 2)]);
    }

    /**
     * @param  array{scale?: int}  $options  то, чего нет в записи работы
     */
    public static function of(Generation $generation, array $options = []): float
    {
        $cost = match ($generation->kind) {
            Generation::KIND_VIDEO => self::video($generation),
            Generation::KIND_AUDIO => self::audio($generation),
            default => self::image($generation, $options),
        };

        return round(max(0.0, (float) $cost), 6);
    }

    /** @param  array{scale?: int}  $options */
    private static function image(Generation $generation, array $options): ?float
    {
        $costs = config('studio.edit.costs', []);

        return match ($generation->operation) {
            Generation::OP_EDIT => $costs['edit'] ?? null,
            Generation::OP_COMBINE => $costs['combine'] ?? null,
            Generation::OP_UPSCALE => $costs['upscale'][$options['scale'] ?? 2] ?? null,
            Generation::OP_BACKGROUND => $costs['background_remove'] ?? null,
            default => self::priced(
                config("studio.models.{$generation->model_key}", []),
                count: max(1, (int) $generation->variants),
            ),
        };
    }

    private static function audio(Generation $generation): ?float
    {
        $seconds = (int) $generation->duration;

        return match ($generation->operation) {
            Generation::OP_SPEECH => self::priced(
                config("studio.speech.models.{$generation->model_key}", []),
                characters: mb_strlen((string) $generation->prompt),
            ),
            Generation::OP_MUSIC => self::priced(
                config("studio.music.models.{$generation->model_key}", []),
                seconds: $seconds,
            ),
            Generation::OP_EFFECT => self::priced(
                config("studio.effects.models.{$generation->model_key}", []),
                seconds: $seconds,
            ),
            // Длина записи до ответа провайдера неизвестна: берём
            // полминуты — типичную голосовую запись.
            Generation::OP_VOICE_CHANGE => self::priced(
                config('studio.voice_changer', []),
                seconds: $seconds ?: 30,
            ),
            default => null,
        };
    }

    private static function video(Generation $generation): ?float
    {
        $mode = $generation->operation === Generation::OP_ANIMATE ? 'image' : 'text';

        return config("studio.video.models.{$generation->model_key}.costs")[$mode][$generation->resolution][(int) $generation->duration] ?? null;
    }

    /**
     * Цена по описанию модели: cost, cost_per_second, cost_per_million
     * или таблица costs по длительности.
     *
     * @param  array<string, mixed>  $model
     */
    private static function priced(array $model, int $seconds = 0, int $characters = 0, int $count = 1): ?float
    {
        return match (true) {
            isset($model['cost']) => $model['cost'] * $count,
            isset($model['cost_per_second']) => $model['cost_per_second'] * $seconds,
            isset($model['cost_per_million']) => $model['cost_per_million'] * $characters / 1_000_000,
            isset($model['costs']) => self::bracket($model['costs'], $seconds),
            default => null,
        };
    }

    /**
     * Цена по таблице длительностей: строка таблицы — «до стольких
     * секунд». 75 секунд в таблице 60/90/120 стоят как 90 — так
     * считает провайдер.
     *
     * @param  array<int, float>  $table
     */
    private static function bracket(array $table, int $seconds): ?float
    {
        ksort($table);

        foreach ($table as $limit => $cost) {
            if ($seconds <= $limit) {
                return $cost;
            }
        }

        return null;
    }
}
