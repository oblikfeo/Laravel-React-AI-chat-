<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\Chat;
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

        $chat->forceFill([
            'model_key' => ModelCatalog::resolve($validated['model']),
        ])->save();

        return back();
    }
}
