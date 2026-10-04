<?php

namespace App\Http\Controllers\Feed;

use App\Actions\Chat\StartChatFromWork;
use App\Http\Controllers\Controller;
use App\Models\Generation;
use App\Services\Guests\CurrentGuest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StoreChatFromFeedController extends Controller
{
    /**
     * Открывает чат с работой из ленты.
     */
    public function __invoke(
        Request $request,
        StartChatFromWork $action,
    ): RedirectResponse {
        $request->validate([
            'generation_id' => ['required', 'integer'],
        ]);

        // Берём только то, что показано в ленте: по номеру скрытую
        // работу не достать.
        $work = Generation::query()
            ->inFeed()
            ->whereKey($request->integer('generation_id'))
            ->first();

        if (! $work) {
            throw new NotFoundHttpException();
        }

        $chat = $action->handle(
            $request->user(),
            CurrentGuest::get($request),
            $work,
        );

        return redirect()->route('chats.show', $chat);
    }
}
