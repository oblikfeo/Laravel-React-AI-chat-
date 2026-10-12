<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\Generation;
use App\Services\Guests\CurrentGuest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DestroyGenerationController extends Controller
{
    /**
     * Удаляет работу вместе с файлом.
     */
    public function __invoke(Request $request, Generation $generation): RedirectResponse
    {
        if (! $this->owns($generation, $request)) {
            throw new AccessDeniedHttpException();
        }

        // Файл удаляем тоже: иначе хранилище забьётся тем, что никому
        // уже не принадлежит.
        $files = array_filter([
            $generation->path,
            $generation->thumbnail_path,
            $generation->source_path,
        ]);

        if ($files && $generation->disk) {
            Storage::disk($generation->disk)->delete($files);
        }

        $generation->delete();

        return back();
    }

    private function owns(Generation $generation, Request $request): bool
    {
        if ($user = $request->user()) {
            return $generation->user_id === $user->id;
        }

        $guest = CurrentGuest::get($request);

        return $guest !== null
            && $generation->guest_id === $guest->id
            && $generation->user_id === null;
    }
}
