<?php

namespace App\Http\Controllers\Characters;

use App\Http\Controllers\Controller;
use App\Models\Character;
use App\Models\Chat;
use App\Services\Guests\ChatOwnership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShowCharacterAvatarController extends Controller
{
    /**
     * Отдаёт аватар персонажа.
     *
     * Файлы лежат вне публичной папки: аватар личного персонажа
     * посторонний не откроет.
     */
    public function __invoke(Request $request, Character $character): StreamedResponse
    {
        if (! $character->avatar_path) {
            throw new NotFoundHttpException();
        }

        // Персонажа могли убрать из каталога уже после того, как
        // человек начал с ним разговор: в своём диалоге аватар
        // остаётся виден.
        if (! Gate::forUser($request->user())->allows('view', $character)
            && ! $this->talksTo($request, $character)) {
            throw new NotFoundHttpException();
        }

        return Storage::disk($character->avatar_disk ?? 'local')->response(
            $character->avatar_path,
            'avatar.'.pathinfo($character->avatar_path, PATHINFO_EXTENSION),
            ['Cache-Control' => 'private, max-age=604800'],
        );
    }

    private function talksTo(Request $request, Character $character): bool
    {
        return Chat::query()
            ->where('character_id', $character->id)
            ->get()
            ->contains(fn (Chat $chat) => ChatOwnership::owns($request, $chat));
    }
}
