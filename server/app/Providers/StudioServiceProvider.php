<?php

namespace App\Providers;

use App\Services\Studio\ImageGenerator;
use App\Services\Studio\OpenRouterImageGenerator;
use App\Services\Studio\UnavailableGenerator;
use Illuminate\Support\ServiceProvider;

class StudioServiceProvider extends ServiceProvider
{
    /**
     * Генерация включается переменной окружения.
     *
     * Одного ключа мало: провайдер открывает доступ к изображениям
     * отдельно от текста, и наличие ключа этого не гарантирует.
     * Поэтому признак задаётся явно, см. STUDIO_ENABLED.
     */
    public function register(): void
    {
        $this->app->singleton(ImageGenerator::class, function () {
            $settings = config('ai.providers.'.config('ai.provider'), []);

            if (! env('STUDIO_ENABLED', false) || blank($settings['api_key'] ?? null)) {
                return new UnavailableGenerator();
            }

            return new OpenRouterImageGenerator(
                baseUrl: $settings['base_url'],
                apiKey: $settings['api_key'],
                timeout: config('studio.timeout'),
            );
        });
    }
}
