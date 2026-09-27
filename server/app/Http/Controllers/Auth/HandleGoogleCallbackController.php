<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Guests\ClaimGuestChats;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\GoogleAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class HandleGoogleCallbackController extends Controller
{
    /**
     * Принимает ответ Google и выполняет вход.
     */
    public function __invoke(Request $request, ClaimGuestChats $claim): RedirectResponse
    {
        if (! GoogleAuth::isConfigured()) {
            return to_route('auth')->with('error', 'This sign-in option is not available right now.');
        }

        try {
            $account = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            // Человек мог отменить вход или закрыть окно. Причина
            // уходит в лог, на экран — обычная фраза.
            Log::warning('Вход через Google не состоялся', [
                'message' => $exception->getMessage(),
            ]);

            return to_route('auth')->with('error', 'We could not sign you in. Please try again.');
        }

        $user = $this->findOrCreate($account);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        // Переписка гостя переходит в учётную запись.
        $claim->handle($request, $user);

        return to_route('home');
    }

    /**
     * Находит учётную запись или создаёт новую.
     *
     * Если почта совпала с существующей, связываем аккаунты: иначе у
     * человека появился бы второй аккаунт с той же почтой, и он не
     * нашёл бы свою переписку.
     */
    private function findOrCreate(object $account): User
    {
        $user = User::where('google_id', $account->getId())->first();

        if ($user) {
            return $user;
        }

        $user = User::where('email', $account->getEmail())->first();

        if ($user) {
            $user->forceFill([
                'google_id' => $account->getId(),
                'avatar' => $account->getAvatar(),
            ])->save();

            return $user;
        }

        return User::create([
            'name' => $account->getName() ?: 'User',
            'email' => $account->getEmail(),
            'google_id' => $account->getId(),
            'avatar' => $account->getAvatar(),
            // Пароля нет: вход только через Google, пока человек не
            // задаст пароль в настройках.
            'password' => null,
        ]);
    }
}
