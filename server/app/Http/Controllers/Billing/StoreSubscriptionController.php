<?php

namespace App\Http\Controllers\Billing;

use App\Actions\Billing\StartSubscription;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\StoreSubscriptionRequest;
use App\Services\Billing\PaymentGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class StoreSubscriptionController extends Controller
{
    /**
     * Оформляет подписку и отправляет на страницу оплаты.
     */
    public function __invoke(
        StoreSubscriptionRequest $request,
        StartSubscription $action,
        PaymentGateway $gateway,
    ): RedirectResponse {
        if (! $gateway->isConfigured()) {
            return back()->with('error', 'Payments are temporarily unavailable. Please try again later.');
        }

        try {
            $url = $action->handle(
                user: $request->user(),
                plan: $request->string('plan')->toString(),
                period: $request->string('period')->toString(),
                returnUrl: route('billing.return'),
            );
        } catch (Throwable $exception) {
            Log::error('Не удалось создать платёж', [
                'user_id' => $request->user()->id,
                'message' => $exception->getMessage(),
            ]);

            return back()->with('error', 'We could not start the payment. Please try again in a moment.');
        }

        return $url
            ? redirect()->away($url)
            : back()->with('error', 'We could not start the payment. Please try again in a moment.');
    }
}
