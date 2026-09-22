<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use Illuminate\Http\RedirectResponse;

class DestroyChatController extends Controller
{
    /**
     * Удаляет чат вместе с сообщениями.
     */
    public function __invoke(Chat $chat): RedirectResponse
    {
        $this->authorize('delete', $chat);

        $chat->delete();

        return to_route('home');
    }
}
