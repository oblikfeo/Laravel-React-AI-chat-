<?php

namespace App\Http\Controllers\Characters;

use App\Http\Controllers\Controller;
use App\Models\Character;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class DestroyCharacterController extends Controller
{
    /**
     * Удаляет персонажа.
     *
     * Переписки с ним остаются у тех, кто их вёл: они становятся
     * обычными чатами.
     */
    public function __invoke(Character $character): RedirectResponse
    {
        $this->authorize('delete', $character);

        if ($character->avatar_path) {
            Storage::disk($character->avatar_disk ?? 'local')
                ->delete($character->avatar_path);
        }

        $character->delete();

        return to_route('characters.index', ['tab' => 'mine'])
            ->with('success', 'Character deleted.');
    }
}
