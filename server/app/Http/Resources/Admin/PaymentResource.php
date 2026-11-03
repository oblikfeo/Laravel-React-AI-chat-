<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Платёж в таблицах админки. Сумма в копейках (центах), как в базе.
 */
class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'plan' => config("plans.list.{$this->plan}.name", $this->plan),
            'period' => $this->period,
            'status' => $this->status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'provider' => $this->provider,
            'providerId' => $this->provider_id,
            'createdAt' => $this->created_at?->format('j M Y, H:i'),
            'paidAt' => $this->paid_at?->format('j M Y, H:i'),
        ];
    }
}
