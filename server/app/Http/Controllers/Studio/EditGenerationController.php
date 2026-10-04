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

        // Объединению нужны две картинки, остальным хватает одной.
        // Чужая работа до сюда не доходит, поэтому список может
        // оказаться короче, чем прислал человек.
        $needed = $request->input('operation') === Generation::OP_COMBINE ? 2 : 1;

        if (count($sources) < $needed) {
            return back()->with(
                'error',
                $needed > 1
                    ? 'Choose at least two images to combine.'
                    : 'Choose an image to edit.',
            );
        }

        $action->handle($user, $guest, $sources, $request->validated());

        return back();
    }

    /**
     * Исходники: готовая работа и/или загруженные файлы.
     *
     * Работа берётся своя или та, что показана в общей ленте: взять
     * за основу чужую картинку из ленты — обычный сценарий. Скрытая
     * чужая работа недоступна, как и при открытии файла.
     *
     * @return array<int, string>
     */
    private function sources(EditGenerationRequest $request, $user, $guest): array
    {
        $sources = [];

        $ids = array_filter((array) $request->input('source_ids', []));

        if ($ids) {
            $generations = Generation::query()
                ->whereKey($ids)
                ->whereNotNull('path')
                ->where(fn ($query) => $query
                    ->where(fn ($own) => $own->ownedBy($user, $guest))
                    ->orWhere(fn ($shared) => $shared->inFeed()))
                ->get();

            foreach ($generations as $generation) {
                $sources[] = Storage::disk($generation->disk)->get($generation->path);
            }
        }

        foreach ($request->file('images', []) as $file) {
            $sources[] = $file->get();
        }

        return array_values(array_filter($sources));
    }
}
