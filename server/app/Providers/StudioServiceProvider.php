<?php

namespace App\Providers;

use App\Services\Studio\AudioStudio;
use App\Services\Studio\ImageGenerator;
use App\Services\Studio\UnavailableAudioStudio;
use App\Services\Studio\UnavailableGenerator;
use App\Services\Studio\Venice\VeniceAudioQueue;
use App\Services\Studio\Venice\VeniceAudioStudio;
use App\Services\Studio\Venice\VeniceClient;
use App\Services\Studio\Venice\VeniceGenerator;
use Illuminate\Support\ServiceProvider;

class StudioServiceProvider extends ServiceProvider
{
    /**
     * Студия включается ключом провайдера.
     *
     * Пока ключа нет, раздел показывает, что скоро откроется, вместо
     * ошибки: интерфейс спрашивает доступность заранее.
     */
    public function register(): void
    {
        $this->app->singleton(ImageGenerator::class, function () {
            $apiKey = config('studio.api_key');

            if (blank($apiKey)) {
                return new UnavailableGenerator();
            }

            return new VeniceGenerator(new VeniceClient(
                baseUrl: config('studio.base_url'),
                apiKey: $apiKey,
                timeout: config('studio.timeout'),
            ));
        });

        $this->app->singleton(AudioStudio::class, function () {
            $apiKey = config('studio.api_key');

            if (blank($apiKey)) {
                return new UnavailableAudioStudio();
            }

            return new VeniceAudioStudio(new VeniceAudioQueue(
                baseUrl: config('studio.base_url'),
                apiKey: $apiKey,
                // Ставить в очередь быстро: ждём не результат, а
                // подтверждение, что задача принята.
                timeout: 60,
            ));
        });
    }
}
