<?php

namespace App\Services\Studio;

/**
 * Данные Студии для интерфейса.
 *
 * Названия моделей у провайдера сюда не попадают: пользователь видит
 * только наши ярлыки.
 */
class StudioCatalog
{
    /** @return array<int, array<string, mixed>> */
    public static function models(): array
    {
        return collect(config('studio.models'))
            ->map(fn (array $model, string $key) => [
                'key' => $key,
                'label' => $model['label'],
                'description' => $model['description'],
                'paid' => (bool) ($model['paid'] ?? false),
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public static function aspectRatios(): array
    {
        return collect(config('studio.aspect_ratios'))
            ->map(fn (array $ratio, string $key) => [
                'key' => $key,
                'label' => $ratio['label'],
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public static function styles(): array
    {
        return collect(config('studio.styles'))
            ->map(fn (array $style, string $key) => [
                'key' => $key,
                'label' => $style['label'],
            ])
            ->values()
            ->all();
    }

    public static function has(string $key): bool
    {
        return (bool) config("studio.models.{$key}");
    }
}
