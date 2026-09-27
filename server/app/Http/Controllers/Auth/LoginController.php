<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Guests\ClaimGuestChats;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;

class LoginController extends Controller
{
    /**
     * Выполняет вход в систему.
     */
    public function __invoke(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Переписка гостя переходит в учётную запись: человек не
        // должен терять разговор, ради которого и регистрируется.
        app(ClaimGuestChats::class)->handle($request, $request->user());

        return redirect()->intended(route('home'));
    }
}
