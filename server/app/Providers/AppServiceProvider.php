<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Без обёртки в ключ "data": Inertia передаёт ресурсы прямо
        // в пропсы страницы, и на клиенте это обычные массивы.
        JsonResource::withoutWrapping();

        // Номер в адресе — только число. Боты перебирают адреса вида
        // /studio/abc/file, и база отвечала на них ошибкой, а сайт —
        // страницей 500 вместо «не найдено».
        foreach (['chat', 'generation', 'character', 'attachment'] as $parameter) {
            Route::pattern($parameter, '[0-9]+');
        }
    }
}
