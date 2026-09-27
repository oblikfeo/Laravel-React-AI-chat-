<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Services\Guests\ChatOwnership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DestroyChatController extends Controller
{
    /**
     * Удаляет чат.
     */
    public function __invoke(Request $request, Chat $chat): RedirectResponse
    {
        if (! ChatOwnership::owns($request, $chat)) {
            throw new AccessDeniedHttpException();
        }

        $chat->delete();

        return to_route('home');
    }
}
