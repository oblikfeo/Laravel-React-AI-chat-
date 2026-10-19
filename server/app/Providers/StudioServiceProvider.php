<?php

namespace App\Providers;

use App\Services\Studio\AudioStudio;
use App\Services\Studio\ImageGenerator;
use App\Services\Studio\UnavailableAudioStudio;
use App\Services\Studio\UnavailableGenerator;
use App\Services\Studio\UnavailableVideoStudio;
use App\Services\Studio\Venice\VeniceAudioStudio;
use App\Services\Studio\Venice\VeniceClient;
use App\Services\Studio\Venice\VeniceGenerator;
use App\Services\Studio\Venice\VeniceQueue;
use App\Services\Studio\Venice\VeniceVideoStudio;
use App\Services\Studio\VideoStudio;
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
                return new UnavailableGenerator;
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
                return new UnavailableAudioStudio;
            }

            return new VeniceAudioStudio($this->queue($apiKey));
        });

        $this->app->singleton(VideoStudio::class, function () {
            $apiKey = config('studio.api_key');

            if (blank($apiKey)) {
                return new UnavailableVideoStudio;
            }

            return new VeniceVideoStudio($this->queue($apiKey));
        });
    }

    private function queue(string $apiKey): VeniceQueue
    {
        return new VeniceQueue(
            baseUrl: config('studio.base_url'),
            apiKey: $apiKey,
            // Поставить задачу быстро, но готовый ролик весит
            // мегабайты и качается дольше подтверждения.
            timeout: 120,
        );
    }
}
