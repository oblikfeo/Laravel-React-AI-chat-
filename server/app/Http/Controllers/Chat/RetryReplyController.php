<?php

namespace App\Http\Controllers\Chat;

use App\Actions\Chat\RequestReply;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Services\Guests\ChatOwnership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RetryReplyController extends Controller
{
    /**
     * Повторяет запрос к модели после неудачи.
     *
     * Неудачный ответ удаляется, иначе в переписке остались бы два
     * ответа на один вопрос: извинение и настоящий.
     */
    public function __invoke(Request $request, Chat $chat, RequestReply $action): RedirectResponse
    {
        if (! ChatOwnership::owns($request, $chat)) {
            throw new AccessDeniedHttpException();
        }

        $last = $chat->messages()->latest('id')->first();

        // Удаляем только несостоявшийся ответ: у него не записана
        // модель, потому что до провайдера дело не дошло.
        if ($last && $last->role === Message::ROLE_ASSISTANT && $last->model === null) {
            $last->delete();
        }

        $action->handle($chat);

        return back();
    }
}
