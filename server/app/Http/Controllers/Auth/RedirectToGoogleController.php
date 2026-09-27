<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\GoogleAuth;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

class RedirectToGoogleController extends Controller
{
    /**
     * Отправляет на страницу входа Google.
     */
    public function __invoke(): RedirectResponse|SymfonyRedirect
    {
        if (! GoogleAuth::isConfigured()) {
            return back()->with('error', 'This sign-in option is not available right now.');
        }

        return Socialite::driver('google')->redirect();
    }
}
