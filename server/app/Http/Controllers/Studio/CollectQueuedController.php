<?php

namespace App\Http\Controllers\Studio;

use App\Actions\Studio\CollectQueued;
use App\Http\Controllers\Controller;
use App\Models\Generation;
use App\Services\Guests\CurrentGuest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CollectQueuedController extends Controller
{
    /**
     * Забирает готовые долгие работы: звук и видео.
     *
     * Страница спрашивает об этом, пока работа считается: результат
     * появляется в галерее сам, без перезагрузки.
     */
    public function __invoke(
        Request $request,
        CollectQueued $action,
    ): RedirectResponse {
        $user = $request->user();
        $guest = CurrentGuest::get($request);

        // Спрашиваем обо всех незаконченных сразу: человек мог
        // запустить несколько и уйти на другую вкладку.
        $waiting = Generation::query()
            ->ownedBy($user, $guest)
            ->where('status', Generation::STATUS_QUEUED)
            ->get();

        $finished = 0;

        foreach ($waiting as $generation) {
            if ($action->handle($generation)->isReady()) {
                $finished++;
            }
        }

        if ($finished === 0) {
            return back();
        }

        return back()->with('success', $finished === 1
            ? 'Done. Saved to your gallery.'
            : "{$finished} works are done. Saved to your gallery.");
    }
}
