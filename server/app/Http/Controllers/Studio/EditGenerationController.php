<?php

namespace App\Http\Controllers\Studio;

use App\Actions\Studio\EditImage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Studio\EditGenerationRequest;
use App\Models\Generation;
use App\Services\Guests\CurrentGuest;
use App\Services\Studio\SourceImages;
use Illuminate\Http\RedirectResponse;

class EditGenerationController extends Controller
{
    /**
     * Правит готовое изображение: по описанию, объединением,
     * увеличением или удалением фона.
     */
    public function __invoke(
        EditGenerationRequest $request,
        EditImage $action,
        SourceImages $images,
    ): RedirectResponse {
        $user = $request->user();
        $guest = CurrentGuest::get($request);

        $sources = $images->collect(
            $user,
            $guest,
            (array) $request->input('source_ids', []),
            $request->file('images', []),
        );

        // Объединению нужны две картинки, остальным хватает одной.
        $needed = $request->input('operation') === Generation::OP_COMBINE ? 2 : 1;

        if (count($sources) < $needed) {
            return back()->with('error', $needed > 1
                ? 'Choose at least two images to combine.'
                : 'Choose an image to edit.');
        }

        $action->handle($user, $guest, $sources, $request->validated());

        return back();
    }
}
