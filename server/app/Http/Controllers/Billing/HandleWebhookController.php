<?php

namespace App\Http\Controllers\Billing;

use App\Actions\Billing\ActivateSubscription;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Billing\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class HandleWebhookController extends Controller
{
    /**
     * Принимает уведомление об оплате от платёжной системы.
     *
     * Отвечаем 200 почти всегда: на другой код платёжная система будет
     * слать уведомление повторно. Отказ имеет смысл только когда
     * уведомление не удалось подтвердить.
     */
    public function __invoke(
        Request $request,
        PaymentGateway $gateway,
        ActivateSubscription $action,
    ): Response {
        $notification = $gateway->parseNotification(
            payload: $request->all(),
            signature: $request->header('Signature'),
        );

        if (! $notification) {
            Log::warning('Не удалось подтвердить уведомление об оплате');

            return response()->noContent(400);
        }

        $payment = Payment::query()
            ->where('provider_id', $notification->paymentId)
            ->first();

        if (! $payment) {
            Log::warning('Уведомление о неизвестном платеже', [
                'provider_id' => $notification->paymentId,
            ]);

            return response()->noContent();
        }

        if ($notification->isSucceeded()) {
            $action->handle($payment, $notification->payload);
        }

        return response()->noContent();
    }
}
