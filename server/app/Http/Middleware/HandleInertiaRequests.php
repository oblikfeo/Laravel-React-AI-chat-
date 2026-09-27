<?php

namespace App\Http\Middleware;

use App\Http\Resources\ChatResource;
use App\Models\User;
use App\Services\Ai\ModelCatalog;
use App\Services\Billing\PaymentGateway;
use App\Services\Guests\CurrentGuest;
use App\Services\Guests\GuestLimiter;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Данные, доступные на всех страницах.
     *
     * Список чатов нужен боковому меню, поэтому он общий.
     * Загружается лениво: Inertia запросит его только при необходимости.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),

            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'plan' => $request->user()->planName(),
                    // Баннер и меню показываются по-разному тем,
                    // кто уже платит, и тем, кто на бесплатном.
                    'canUpgrade' => $request->user()->isPromotedPlan(),
                    'subscription' => $this->subscriptionOf($request->user()),
                ] : null,
            ],

            // Гость тоже видит свои чаты: он пользуется сервисом,
            // просто ограниченно.
            'sidebarChats' => fn () => $this->chatsOf($request),

            'guest' => fn () => $this->guestState($request),

            // Список моделей общий: он нужен и на главной, и в диалоге.
            'models' => fn () => ModelCatalog::forInterface(),
            'defaultModel' => config('models.default'),

            // Интерфейс скрывает кнопки оплаты, пока реквизиты
            // платёжной системы не заданы.
            'billingReady' => fn () => app(PaymentGateway::class)->isConfigured(),

            'flash' => [
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * Краткие сведения о подписке для интерфейса.
     */
    private function subscriptionOf(User $user): ?array
    {
        $subscription = $user->activeSubscription();

        if (! $subscription) {
            return null;
        }

        return [
            'endsAt' => $subscription->ends_at?->format('j M Y'),
            'cancelled' => $subscription->cancelled_at !== null,
        ];
    }

    /**
     * Чаты владельца: пользователя или гостя.
     */
    private function chatsOf(Request $request): mixed
    {
        $owner = $request->user() ?? CurrentGuest::get($request);

        if (! $owner) {
            return [];
        }

        return ChatResource::collection($owner->chats()->limit(30)->get());
    }

    /**
     * Состояние гостя для интерфейса.
     *
     * Авторизованному не нужно: у него нет ограничений гостя.
     */
    private function guestState(Request $request): ?array
    {
        if ($request->user()) {
            return null;
        }

        $guest = CurrentGuest::get($request);

        if (! $guest) {
            return null;
        }

        return [
            'remaining' => app(GuestLimiter::class)->remaining($guest),
            'limit' => config('guests.daily_messages'),
        ];
    }
}
