<?php

namespace App\Http\Controllers\Studio;

use App\Actions\Studio\CollectAudio;
use App\Http\Controllers\Controller;
use App\Models\Generation;
use App\Services\Guests\CurrentGuest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CollectAudioController extends Controller
{
    /**
     * Забирает готовую звуковую работу.
     *
     * Страница спрашивает об этом, пока работа считается: результат
     * появляется в галерее сам, без перезагрузки.
     */
    public function __invoke(
        Request $request,
        CollectAudio $action,
    ): RedirectResponse {
        $user = $request->user();
        $guest = CurrentGuest::get($request);

        // Спрашиваем обо всех незаконченных сразу: человек мог
        // запустить несколько и уйти на другую вкладку.
        $waiting = Generation::query()
            ->ownedBy($user, $guest)
            ->where('status', Generation::STATUS_QUEUED)
            ->get();

        foreach ($waiting as $generation) {
            $action->handle($generation);
        }

        return back();
    }
}
