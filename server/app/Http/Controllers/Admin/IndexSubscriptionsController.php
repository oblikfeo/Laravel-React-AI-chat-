<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\SubscriptionResource;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexSubscriptionsController extends Controller
{
    private const STATUSES = ['active', 'pending', 'cancelled', 'expired'];

    /**
     * Подписки с расходом бюджета. Фильтры — состояние и тариф.
     */
    public function __invoke(Request $request): Response
    {
        $status = in_array($request->query('status'), self::STATUSES, true) ? $request->query('status') : null;
        $plan = array_key_exists((string) $request->query('plan'), config('plans.list')) ? $request->query('plan') : null;

        $subscriptions = Subscription::query()
            ->with('user')
            ->when($status === 'active', fn ($query) => $query->active())
            ->when($status && $status !== 'active', fn ($query) => $query->where('status', $status))
            ->when($plan, fn ($query) => $query->where('plan', $plan))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Subscriptions', [
            'subscriptions' => SubscriptionResource::collection($subscriptions),
            'filters' => ['status' => $status, 'plan' => $plan],
            'statuses' => self::STATUSES,
            'plans' => collect(config('plans.list'))
                ->except(config('plans.default'))
                ->map(fn (array $item, string $key) => ['key' => $key, 'label' => $item['name']])
                ->values(),
        ]);
    }
}
