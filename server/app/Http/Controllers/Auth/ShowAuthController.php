<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowAuthController extends Controller
{
    /**
     * Единая страница авторизации.
     *
     * Вход, регистрация и восстановление живут в одном роуте и
     * переключаются на клиенте без перезагрузки. Параметр `mode`
     * задаёт, какая форма открыта изначально.
     */
    public function __invoke(Request $request): Response
    {
        $mode = $request->query('mode', 'login');

        if (! in_array($mode, ['login', 'register', 'forgot'], true)) {
            $mode = 'login';
        }

        return Inertia::render('Auth/Index', [
            'mode' => $mode,
        ]);
    }
}
