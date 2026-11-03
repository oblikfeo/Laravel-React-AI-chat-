<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\PaymentResource;
use App\Http\Resources\Admin\SubscriptionResource;
use App\Models\UsageRecord;
use App\Models\User;
use App\Services\Usage\UsageMeter;
use Inertia\Inertia;
use Inertia\Response;

class ShowUserController extends Controller
{
    /**
     * Всё о пользователе для поддержки: тариф, расход бюджета,
     * подписки, платежи и последние траты.
     */
    public function __invoke(User $user, UsageMeter $meter): Response
    {
        $subscriptions = $user->subscriptions()->get()->each->setRelation('user', $user);

        return Inertia::render('Admin/User', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'plan' => $user->planName(),
                'isAdmin' => $user->isAdmin(),
                'joinedAt' => $user->created_at?->format('j M Y'),
                'google' => filled($user->google_id),
            ],
            'usage' => $meter->snapshot($user)->toAdminArray(),
            'subscriptions' => SubscriptionResource::collection($subscriptions),
            'payments' => PaymentResource::collection($user->payments()->get()),
            'records' => $user->usageRecords()
                ->latest('id')
                ->limit(50)
                ->get()
                ->map(fn (UsageRecord $record) => [
                    'id' => $record->id,
                    'category' => $record->category,
                    'model' => $record->model_key,
                    'providerModel' => $record->provider_model,
                    'cost' => $record->cost,
                    'meta' => $record->meta,
                    'createdAt' => $record->created_at?->format('j M, H:i'),
                ]),
        ]);
    }
}
