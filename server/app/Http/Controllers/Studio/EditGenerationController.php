<?php

namespace App\Http\Controllers\Studio;

use App\Actions\Studio\EditImage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Studio\EditGenerationRequest;
use App\Models\Generation;
use App\Services\Guests\CurrentGuest;
use App\Services\Studio\ImageGenerator;
use App\Services\Studio\StudioLimiter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class EditGenerationController extends Controller
{
    /**
     * Правит готовое изображение: по описанию, объединением,
     * увеличением или удалением фона.
     */
    public function __invoke(
        EditGenerationRequest $request,
        EditImage $action,
        StudioLimiter $limiter,
        ImageGenerator $generator,
    ): RedirectResponse {
        if (! $generator->isAvailable()) {
            return back()->with('error', 'Studio is not available yet. It is coming soon.');
        }

        $user = $request->user();
        $guest = CurrentGuest::get($request);

        if (! $limiter->allows($user, $guest)) {
            return back()->with(
                'error',
                $user
                    ? "You have reached today's limit. It resets tomorrow."
                    : "You have reached today's free limit. Sign up to create more."
            );
        }

        $sources = $this->sources($request, $user, $guest);

        if (! $sources) {
            return back()->with('error', 'Choose an image to edit.');
        }

        $action->handle($user, $guest, $sources, $request->validated());

        return back();
    }

    /**
     * Исходники: своя готовая работа и/или загруженные файлы.
     *
     * Чужую работу взять нельзя — проверяем владельца, как и при
     * открытии файла.
     *
     * @return array<int, string>
     */
    private function sources(EditGenerationRequest $request, $user, $guest): array
    {
        $sources = [];

        if ($id = $request->integer('source_generation_id')) {
            $generation = Generation::query()
                ->ownedBy($user, $guest)
                ->whereKey($id)
                ->whereNotNull('path')
                ->first();

            if ($generation) {
                $sources[] = Storage::disk($generation->disk)->get($generation->path);
            }
        }

        foreach ($request->file('images', []) as $file) {
            $sources[] = $file->get();
        }

        return array_values(array_filter($sources));
    }
}
