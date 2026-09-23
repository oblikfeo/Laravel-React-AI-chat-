<?php

namespace App\Services\Ai;

/**
 * Каталог моделей.
 *
 * Отвечает на три вопроса: какая модель выбрана, понимает ли она
 * картинки и чем её заменить, если она не ответила.
 */
class ModelCatalog
{
    /** Ключ существующей модели либо ключ по умолчанию. */
    public static function resolve(?string $key): string
    {
        return $key && self::has($key) ? $key : config('models.default');
    }

    public static function has(string $key): bool
    {
        return (bool) config("models.list.{$key}");
    }

    /** Название модели у провайдера. Пользователю не показывается. */
    public static function providerModel(string $key): string
    {
        return config("models.list.{$key}.provider_model");
    }

    /** Название модели для интерфейса. */
    public static function labelOf(string $key): string
    {
        return config("models.list.{$key}.label", 'Auto');
    }

    public static function supportsVision(string $key): bool
    {
        return (bool) config("models.list.{$key}.vision");
    }

    /**
     * Модель для запроса с учётом вложений.
     *
     * Если к сообщению приложена картинка, а выбранная модель их не
     * понимает, подставляем ту, которая умеет: человек не обязан
     * помнить, какая модель что может.
     */
    public static function forRequest(string $key, bool $hasImages): string
    {
        if ($hasImages && ! self::supportsVision($key)) {
            return self::resolve(config('models.vision_fallback'));
        }

        return $key;
    }

    /** Чем заменить модель, если она не ответила. */
    public static function fallback(string $key): ?string
    {
        $next = config("models.list.{$key}.fallback");

        return $next && self::has($next) ? $next : null;
    }

    /**
     * Список для интерфейса.
     *
     * Названия моделей у провайдера сюда не попадают: это внутренняя
     * деталь, пользователь видит только наши ярлыки.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function forInterface(): array
    {
        return collect(config('models.list'))
            ->map(fn (array $model, string $key) => [
                'key' => $key,
                'label' => $model['label'],
                'description' => $model['description'],
                'vision' => (bool) ($model['vision'] ?? false),
                'free' => (bool) ($model['free'] ?? false),
            ])
            ->values()
            ->all();
    }
}
