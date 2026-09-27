<?php

namespace App\Http\Controllers\Chat;

use App\Actions\Chat\RequestReply;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Services\Guests\ChatOwnership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ReplyController extends Controller
{
    /**
     * Запрашивает ответ модели на последнее сообщение.
     *
     * Вызывается из открытого диалога, поэтому ожидание видно
     * пользователю как индикатор набора, а не как полоса загрузки.
     */
    public function __invoke(Request $request, Chat $chat, RequestReply $action): RedirectResponse
    {
        if (! ChatOwnership::owns($request, $chat)) {
            throw new AccessDeniedHttpException();
        }

        $action->handle($chat);

        return back();
    }
}
