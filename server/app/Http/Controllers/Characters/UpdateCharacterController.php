<?php

namespace App\Http\Controllers\Characters;

use App\Actions\Characters\SaveCharacter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Characters\SaveCharacterRequest;
use App\Models\Character;
use Illuminate\Http\RedirectResponse;

class UpdateCharacterController extends Controller
{
    /**
     * Сохраняет изменения в персонаже.
     *
     * Запрос приходит как POST: вместе с полями едут файлы, а
     * составные формы другими методами браузер не отправляет.
     */
    public function __invoke(
        SaveCharacterRequest $request,
        Character $character,
        SaveCharacter $action,
    ): RedirectResponse {
        $this->authorize('update', $character);

        $action->handle(
            $request->user(),
            $character,
            $request->validated(),
            $request->file('avatar'),
            $request->file('context'),
        );

        return to_route('characters.index', ['tab' => 'mine'])
            ->with('success', 'Character saved.');
    }
}
