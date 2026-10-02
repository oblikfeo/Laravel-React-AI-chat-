<?php

namespace App\Services\Studio;

use App\Models\User;

/**
 * Данные Студии для интерфейса.
 *
 * Названия моделей у провайдера сюда не попадают: пользователь видит
 * только наши ярлыки.
 */
class StudioCatalog
{
    /** @return array<int, array<string, mixed>> */
    public static function models(?User $user = null): array
    {
        return collect(config('studio.models'))
            ->map(fn (array $model, string $key) => [
                'key' => $key,
                'label' => $model['label'],
                'description' => $model['description'],
                'paid' => (bool) ($model['paid'] ?? false),
                'signature' => (bool) ($model['signature'] ?? false),
                'badge' => ($model['paid'] ?? false) ? 'Pro' : null,
                // Платные модели видны всем, но без подписки недоступны:
                // скрывать их бессмысленно — человек должен понимать,
                // что получит.
                'locked' => ($model['paid'] ?? false) && ! self::isPaid($user),
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
                // Пропорция и размер: по одному названию не понять,
                // что получишь.
                'badge' => $key,
                'description' => sprintf(
                    '%s · %d×%d',
                    $ratio['hint'],
                    $ratio['width'],
                    $ratio['height'],
                ),
            ])
            ->values()
            ->all();
    }

    /**
     * Стили.
     *
     * Ключ наш, значение — название в справочнике провайдера. Ярлык
     * получаем из ключа, чтобы не держать одно и то же дважды.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function styles(): array
    {
        $labels = [
            'none' => ['None', 'Follow the prompt only'],
            'photo' => ['Photo', 'Realistic, natural light'],
            'cinematic' => ['Cinematic', 'Film look, dramatic light'],
            'anime' => ['Anime', 'Japanese animation'],
            'art' => ['Digital art', 'Painted, rich colour'],
            'render' => ['3D render', 'Modelled and lit in 3D'],
            'comic' => ['Comic', 'Bold ink and halftones'],
            'neon' => ['Neon punk', 'Night city, glowing signs'],
            'pixel' => ['Pixel art', 'Retro game sprites'],
            'minimal' => ['Line art', 'Clean outlines, no fill'],
        ];

        return collect(config('studio.styles'))
            ->map(fn (?string $preset, string $key) => [
                'key' => $key,
                'label' => $labels[$key][0] ?? ucfirst($key),
                'description' => $labels[$key][1] ?? null,
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    public static function speechModels(?User $user = null): array
    {
        return collect(config('studio.speech.models'))
            ->map(fn (array $model, string $key) => [
                'key' => $key,
                'label' => $model['label'],
                'description' => $model['description'],
                'paid' => (bool) ($model['paid'] ?? false),
                'badge' => ($model['paid'] ?? false) ? 'Pro' : null,
                'locked' => ($model['paid'] ?? false) && ! self::isPaid($user),
            ])
            ->values()
            ->all();
    }

    /** Есть ли у человека платный тариф. */
    private static function isPaid(?User $user): bool
    {
        return $user !== null
            && ! in_array($user->plan, config('plans.promoted'), true);
    }

    public static function has(string $key): bool
    {
        return (bool) config("studio.models.{$key}");
    }

    public static function hasSpeechModel(string $key): bool
    {
        return (bool) config("studio.speech.models.{$key}");
    }
}
