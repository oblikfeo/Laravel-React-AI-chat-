<?php

namespace App\Providers;

use App\Services\Ai\AiChatProvider;
use App\Services\Ai\FakeProvider;
use App\Services\Ai\OpenAiCompatibleProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    /**
     * Выбирает реализацию провайдера по конфигурации.
     *
     * Если ключ не задан, подставляется запасной вариант, который отвечает
     * нейтральным текстом. О самой проблеме сообщаем в лог: пользователь
     * о настройках знать не должен.
     */
    public function register(): void
    {
        $this->app->singleton(AiChatProvider::class, function (): AiChatProvider {
            $name = config('ai.provider');
            $settings = config("ai.providers.{$name}");

            if (! $settings || blank($settings['api_key'])) {
                Log::warning('Ключ AI-провайдера не задан, отвечаем запасным текстом', [
                    'provider' => $name,
                ]);

                return new FakeProvider();
            }

            return new OpenAiCompatibleProvider(
                baseUrl: rtrim($settings['base_url'], '/'),
                apiKey: $settings['api_key'],
                model: $settings['model'],
                timeout: config('ai.timeout'),
                maxTokens: config('ai.max_tokens'),
            );
        });
    }
}
