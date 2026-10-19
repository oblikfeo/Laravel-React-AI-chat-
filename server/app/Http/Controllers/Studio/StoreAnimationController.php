<?php

namespace App\Http\Controllers\Studio;

use App\Actions\Studio\QueueVideo;
use App\Http\Controllers\Controller;
use App\Http\Requests\Studio\StoreAnimationRequest;
use App\Services\Studio\SourceImages;
use Illuminate\Http\RedirectResponse;

class StoreAnimationController extends Controller
{
    /**
     * Ставит в работу оживление картинки.
     */
    public function __invoke(
        StoreAnimationRequest $request,
        QueueVideo $action,
        SourceImages $images,
    ): RedirectResponse {
        $user = $request->user();

        $source = $images->one($user, $request->integer('source_id') ?: null, $request->file('image'));

        if (! $source) {
            return back()->with('error', 'Choose an image to animate.');
        }

        [$image, $mime] = $source;

        $action->fromImage($user, $image, $mime, $request->validated());

        return back();
    }
}
