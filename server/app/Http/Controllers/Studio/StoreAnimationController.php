<?php

namespace App\Http\Controllers\Studio;

use App\Actions\Studio\QueueVideo;
use App\Http\Controllers\Controller;
use App\Http\Requests\Studio\StoreAnimationRequest;
use App\Models\Generation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class StoreAnimationController extends Controller
{
    /**
     * Ставит в работу оживление картинки.
     *
     * Кому можно снимать и сколько, проверяет EnsureVideoAccess.
     */
    public function __invoke(StoreAnimationRequest $request, QueueVideo $action): RedirectResponse
    {
        $user = $request->user();

        [$image, $mime] = $this->source($request, $user);

        if ($image === null) {
            return back()->with('error', 'Choose an image to animate.');
        }

        $action->fromImage($user, $image, $mime, $request->validated());

        return back();
    }

    /**
     * Картинка для оживления: файл с диска или своя работа.
     *
     * Чужую работу по номеру взять нельзя: запрос ограничен своими.
     *
     * @return array{0: ?string, 1: string}
     */
    private function source(StoreAnimationRequest $request, User $user): array
    {
        if ($file = $request->file('image')) {
            return [$file->get(), $file->getMimeType() ?: 'image/png'];
        }

        $generation = Generation::query()
            ->whereKey($request->integer('source_id'))
            ->where('user_id', $user->id)
            ->where('kind', Generation::KIND_IMAGE)
            ->whereNotNull('path')
            ->first();

        if (! $generation) {
            return [null, 'image/png'];
        }

        return [
            Storage::disk($generation->disk)->get($generation->path),
            $generation->mime ?: 'image/png',
        ];
    }
}
