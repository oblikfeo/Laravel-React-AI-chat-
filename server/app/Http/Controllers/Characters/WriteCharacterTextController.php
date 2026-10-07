<?php

namespace App\Http\Controllers\Characters;

use App\Actions\Characters\WriteCharacterText;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

class WriteCharacterTextController extends Controller
{
    /**
     * Дописывает описание или инструкции персонажа.
     *
     * Отвечает данными, а не страницей: текст подставляется в
     * открытую форму, перезагружать её нельзя.
     */
    public function __invoke(Request $request, WriteCharacterText $action): JsonResponse
    {
        $limits = config('characters.limits');

        $draft = $request->validate([
            'target' => ['required', Rule::in([
                WriteCharacterText::DESCRIPTION,
                WriteCharacterText::INSTRUCTIONS,
            ])],
            'name' => ['nullable', 'string', 'max:'.$limits['name']],
            'description' => ['nullable', 'string', 'max:'.$limits['description']],
            'instructions' => ['nullable', 'string', 'max:'.$limits['instructions']],
        ]);

        // Писать не из чего — просим хотя бы имя.
        if (blank($draft['name'] ?? null)
            && blank($draft['description'] ?? null)
            && blank($draft['instructions'] ?? null)) {
            return response()->json([
                'message' => 'Fill in the name first, so there is something to build on.',
            ], 422);
        }

        try {
            return response()->json([
                'text' => $action->handle($draft['target'], $draft),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Персонажи: не удалось дописать текст', [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Could not write it right now. Please try again in a moment.',
            ], 503);
        }
    }
}
