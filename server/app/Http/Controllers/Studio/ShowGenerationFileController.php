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
        if (! $generation->isReady()) {
            throw new AccessDeniedHttpException();
        }

        // Работу видит владелец, а чужую — только если её показали в
        // общей ленте. Скрытая остаётся закрытой для всех остальных.
        if (! $this->owns($generation, $request) && ! $this->inFeed($generation)) {
            throw new AccessDeniedHttpException();
        }

        $download = $request->boolean('download');

        // В ленте показываем уменьшенную копию: полный файл весит
        // мегабайты, а квадрат в ленте — двести точек.
        $path = $request->boolean('small') && $generation->thumbnail_path
            ? $generation->thumbnail_path
            : $generation->path;

        $name = 'uncensia-'.$generation->id.'.'.pathinfo($path, PATHINFO_EXTENSION);

        $disk = Storage::disk($generation->disk);

        return $download
            ? $disk->download($generation->path, 'uncensia-'.$generation->id.'.'.pathinfo($generation->path, PATHINFO_EXTENSION))
            : $disk->response($path, $name, [
                // Работа не меняется, поэтому браузер может держать
                // её у себя и не спрашивать заново.
                'Cache-Control' => 'private, max-age=604800',
            ]);
    }

    /**
     * Показана ли работа в общей ленте.
     *
     * Условия те же, что у самой ленты: иначе по прямой ссылке
     * открылось бы то, чего в ленте нет.
     */
    private function inFeed(Generation $generation): bool
    {
        return Generation::query()
            ->inFeed()
            ->whereKey($generation->id)
            ->exists();
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
