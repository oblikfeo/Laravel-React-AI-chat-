<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChatResource;
use App\Services\Guests\CurrentGuest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexChatController extends Controller
{
    /**
     * Список чатов: свои у пользователя, свои у гостя.
     */
    public function __invoke(Request $request): Response
    {
        $owner = $request->user() ?? CurrentGuest::get($request);

        return Inertia::render('Chat/Index', [
            'chats' => ChatResource::collection(
                $owner ? $owner->chats()->get() : collect()
            ),
        ]);
    }
}
