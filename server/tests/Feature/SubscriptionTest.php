<?php

namespace Tests\Feature;

use App\Actions\Billing\ActivateSubscription;
use App\Actions\Billing\CancelSubscription;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function pendingPayment(User $user, string $plan = 'pro', string $period = 'monthly'): Payment
    {
        $subscription = $user->subscriptions()->create([
            'plan' => $plan,
            'period' => $period,
            'status' => Subscription::STATUS_PENDING,
            'amount' => 1800,
            'currency' => 'RUB',
        ]);

        return $user->payments()->create([
            'subscription_id' => $subscription->id,
            'plan' => $plan,
            'period' => $period,
            'status' => Payment::STATUS_PENDING,
            'amount' => 1800,
            'currency' => 'RUB',
            'provider_id' => 'test-'.uniqid(),
        ]);
    }

    public function test_paid_subscription_activates_the_plan(): void
    {
        $user = User::factory()->create();
        $payment = $this->pendingPayment($user);

        (new ActivateSubscription())->handle($payment);

        $this->assertSame('pro', $user->fresh()->plan);
        $this->assertSame(Payment::STATUS_SUCCEEDED, $payment->fresh()->status);
        $this->assertTrue($user->fresh()->activeSubscription()->isActive());
    }

    public function test_monthly_subscription_lasts_a_month(): void
    {
        $user = User::factory()->create();
        $payment = $this->pendingPayment($user, period: 'monthly');

        (new ActivateSubscription())->handle($payment);

        $ends = $user->fresh()->activeSubscription()->ends_at;

        $this->assertEqualsWithDelta(
            now()->addMonth()->timestamp,
            $ends->timestamp,
            5,
        );
    }

    public function test_yearly_subscription_lasts_a_year(): void
    {
        $user = User::factory()->create();
        $payment = $this->pendingPayment($user, period: 'yearly');

        (new ActivateSubscription())->handle($payment);

        $this->assertEqualsWithDelta(
            now()->addYear()->timestamp,
            $user->fresh()->activeSubscription()->ends_at->timestamp,
            5,
        );
    }

    /**
     * Платёжная система присылает уведомление несколько раз.
     */
    public function test_repeated_notification_does_not_extend_the_period(): void
    {
        $user = User::factory()->create();
        $payment = $this->pendingPayment($user);
        $action = new ActivateSubscription();

        $action->handle($payment);
        $first = $user->fresh()->activeSubscription()->ends_at;

        $action->handle($payment->fresh());
        $second = $user->fresh()->activeSubscription()->ends_at;

        $this->assertSame($first->timestamp, $second->timestamp);
    }

    public function test_cancelled_subscription_works_until_the_period_ends(): void
    {
        $user = User::factory()->create();
        $payment = $this->pendingPayment($user);
        (new ActivateSubscription())->handle($payment);

        $subscription = $user->fresh()->activeSubscription();
        (new CancelSubscription())->handle($subscription);

        // Тариф остаётся: оплаченный срок не сгорает.
        $this->assertSame('pro', $user->fresh()->plan);
        $this->assertTrue($subscription->fresh()->isActive());
    }

    public function test_expired_subscription_returns_the_free_plan(): void
    {
        $user = User::factory()->create();
        $payment = $this->pendingPayment($user);
        (new ActivateSubscription())->handle($payment);

        $user->activeSubscription()->forceFill([
            'ends_at' => now()->subDay(),
        ])->save();

        $this->artisan('subscriptions:expire')->assertSuccessful();

        $this->assertSame('free', $user->fresh()->plan);
        $this->assertNull($user->fresh()->activeSubscription());
    }

    public function test_guest_cannot_subscribe(): void
    {
        $this->post('/billing/subscribe', [
            'plan' => 'pro',
            'period' => 'monthly',
        ])->assertRedirect();

        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_free_plan_cannot_be_purchased(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/billing/subscribe', [
                'plan' => 'free',
                'period' => 'monthly',
            ])
            ->assertSessionHasErrors('plan');
    }

    public function test_payment_is_not_offered_without_credentials(): void
    {
        config(['billing.providers.yookassa.shop_id' => null]);
        config(['billing.providers.yookassa.secret_key' => null]);

        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertInertia(fn ($page) => $page->where('billingReady', false));
    }
}
