<?php

namespace App\Http\Resources\Admin;

use App\Services\Usage\UsageMeter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Подписка в таблицах админки.
 *
 * У действующей — расход бюджета в текущем месяце подписки: видно,
 * кто упирается в тариф и кому пора предложить старший.
 */
class SubscriptionResource extends JsonResource
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
            'status' => $this->isActive() && $this->cancelled_at ? 'cancelling' : $this->status,
            'active' => $this->isActive(),
            'amount' => $this->amount,
            'currency' => $this->currency,
            'provider' => $this->provider,
            'startsAt' => $this->starts_at?->format('j M Y'),
            'endsAt' => $this->ends_at?->format('j M Y'),
            'usage' => $this->isActive() && $this->relationLoaded('user')
                ? app(UsageMeter::class)->snapshot($this->user)->toAdminArray()
                : null,
        ];
    }
}
