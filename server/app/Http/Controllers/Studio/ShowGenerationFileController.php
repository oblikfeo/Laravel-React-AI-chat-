<?php

namespace App\Http\Controllers\Studio;

use App\Http\Controllers\Controller;
use App\Models\Generation;
use App\Services\Guests\CurrentGuest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ShowGenerationFileController extends Controller
{
    /**
     * Отдаёт готовое изображение.
     *
     * Файлы лежат вне публичной папки, поэтому доступ идёт через
     * приложение: чужую работу открыть нельзя.
     */
    public function __invoke(Request $request, Generation $generation): StreamedResponse
    {
        if (! $this->owns($generation, $request) || ! $generation->isReady()) {
            throw new AccessDeniedHttpException();
        }

        $download = $request->boolean('download');
        $name = 'uncensia-'.$generation->id.'.'.pathinfo($generation->path, PATHINFO_EXTENSION);

        $disk = Storage::disk($generation->disk);

        return $download
            ? $disk->download($generation->path, $name)
            : $disk->response($generation->path, $name);
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
