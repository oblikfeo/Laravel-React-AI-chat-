<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    /**
     * Куда попадает человек после страницы оплаты.
     *
     * Подписку включает уведомление от платёжной системы, а не этот
     * переход: на него можно попасть и без оплаты.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $paid = $request->user()?->activeSubscription() !== null;

        return redirect()->route('home')->with(
            $paid ? 'success' : 'notice',
            $paid
                ? 'Your subscription is active. Enjoy!'
                : 'We are confirming your payment. This usually takes a moment.',
        );
    }
}
