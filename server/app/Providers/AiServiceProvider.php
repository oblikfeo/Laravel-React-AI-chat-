<?php

namespace App\Providers;

use App\Services\Ai\AiChatProvider;
use App\Services\Ai\FakeProvider;
use App\Services\Ai\OpenAiCompatibleProvider;
use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    /**
     * Выбирает реализацию провайдера по конфигурации.
     *
     * Пока ключ не задан, подставляется заглушка, поэтому интерфейс
     * остаётся рабочим без внешних сервисов.
     */
    public function register(): void
    {
        $this->app->singleton(AiChatProvider::class, function (): AiChatProvider {
            $name = config('ai.provider');
            $settings = config("ai.providers.{$name}");

            if (! $settings || blank($settings['api_key'])) {
                return new FakeProvider();
            }

            return new OpenAiCompatibleProvider(
                baseUrl: rtrim($settings['base_url'], '/'),
                apiKey: $settings['api_key'],
                model: $settings['model'],
                timeout: config('ai.timeout'),
            );
        });
    }
}
