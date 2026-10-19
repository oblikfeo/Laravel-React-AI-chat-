<?php

namespace App\Http\Controllers\Studio;

use App\Actions\Studio\CreateGeneration;
use App\Http\Controllers\Controller;
use App\Http\Requests\Studio\StoreGenerationRequest;
use App\Services\Guests\CurrentGuest;
use Illuminate\Http\RedirectResponse;

class StoreGenerationController extends Controller
{
    /**
     * Создаёт изображение по описанию.
     *
     * Доступность Студии и дневной лимит проверяет EnsureStudioAccess.
     */
    public function __invoke(StoreGenerationRequest $request, CreateGeneration $action): RedirectResponse
    {
        $user = $request->user();
        $guest = CurrentGuest::get($request);

        $action->handle($user, $guest, $request->validated());

        return back();
    }
}
