<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Services\Ai\ModelCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateChatModelController extends Controller
{
    /**
     * Меняет модель в открытом чате.
     */
    public function __invoke(Request $request, Chat $chat): RedirectResponse
    {
        $this->authorize('update', $chat);

        $validated = $request->validate([
            'model' => ['required', Rule::in(array_keys(config('models.list')))],
        ]);

        $key = ModelCatalog::resolve($validated['model']);

        if ($key === $chat->model_key) {
            return back();
        }

        $chat->forceFill(['model_key' => $key])->save();

        // Отметка в ленте: видно, с какого места отвечает другая модель.
        // Пустой чат не помечаем — там менять ещё нечего.
        if ($chat->messages()->exists()) {
            $chat->messages()->create([
                'role' => Message::ROLE_SYSTEM,
                'content' => ModelCatalog::labelOf($key),
            ]);
        }

        return back();
    }
}
