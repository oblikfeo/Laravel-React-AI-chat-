<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    /**
     * Создаёт аккаунт и сразу выполняет вход.
     */
    public function __invoke(RegisterRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        return to_route('home');
    }
}
