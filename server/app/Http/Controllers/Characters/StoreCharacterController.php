<?php

namespace App\Http\Controllers\Characters;

use App\Actions\Characters\SaveCharacter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Characters\SaveCharacterRequest;
use Illuminate\Http\RedirectResponse;

class StoreCharacterController extends Controller
{
    /**
     * Создаёт персонажа.
     */
    public function __invoke(SaveCharacterRequest $request, SaveCharacter $action): RedirectResponse
    {
        $user = $request->user();

        if ($user->characters()->count() >= config('characters.max_per_user')) {
            return back()->with('error', 'You have reached the limit of characters. Delete one to create another.');
        }

        $action->handle(
            $user,
            null,
            $request->validated(),
            $request->file('avatar'),
            $request->file('context'),
        );

        return to_route('characters.index', ['tab' => 'mine'])
            ->with('success', 'Character created.');
    }
}
