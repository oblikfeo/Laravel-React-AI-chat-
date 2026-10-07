<?php

namespace App\Http\Controllers\Characters;

use App\Actions\Characters\StartCharacterChat;
use App\Http\Controllers\Controller;
use App\Models\Character;
use App\Services\Guests\CurrentGuest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StartCharacterChatController extends Controller
{
    /**
     * Открывает диалог с персонажем: прежний, если он уже был.
     */
    public function __invoke(
        Request $request,
        Character $character,
        StartCharacterChat $action,
    ): RedirectResponse {
        $user = $request->user();

        // Закрытого персонажа не выдаём даже существованием.
        if (! Gate::forUser($user)->allows('view', $character)) {
            throw new NotFoundHttpException();
        }

        $guest = CurrentGuest::get($request);

        // Без учётной записи и без гостя чат некому принадлежать.
        if (! $user && ! $guest) {
            return redirect('/auth?mode=register');
        }

        return to_route('chats.show', $action->handle($character, $user, $guest));
    }
}
