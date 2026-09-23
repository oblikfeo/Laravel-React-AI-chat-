<?php

namespace App\Http\Controllers\Chat;

use App\Actions\Chat\RequestReply;
use App\Http\Controllers\Controller;
use App\Models\Chat;
use Illuminate\Http\RedirectResponse;

class ReplyController extends Controller
{
    /**
     * Запрашивает ответ модели на последнее сообщение.
     *
     * Вызывается из открытого диалога, поэтому ожидание видно
     * пользователю как индикатор набора, а не как полоса загрузки.
     */
    public function __invoke(Chat $chat, RequestReply $action): RedirectResponse
    {
        $this->authorize('update', $chat);

        $action->handle($chat);

        return back();
    }
}
