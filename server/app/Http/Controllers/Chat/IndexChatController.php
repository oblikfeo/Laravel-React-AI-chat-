<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChatResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexChatController extends Controller
{
    /**
     * Список чатов пользователя.
     */
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Chat/Index', [
            'chats' => ChatResource::collection($request->user()->chats),
        ]);
    }
}
