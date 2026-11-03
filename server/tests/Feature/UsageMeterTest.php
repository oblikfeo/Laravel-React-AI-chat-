<?php

namespace Tests\Feature;

use App\Models\Generation;
use App\Models\Subscription;
use App\Models\UsageRecord;
use App\Models\User;
use App\Services\Studio\ImageGenerator;
use App\Services\Usage\UsageMeter;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeImageGenerator;
use Tests\TestCase;

/**
 * Бюджет тарифа: период, проценты, перенос остатка, дневной предел.
 */
class UsageMeterTest extends TestCase
{
    use RefreshDatabase;

    private UsageMeter $meter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->meter = app(UsageMeter::class);
        $this->travelTo(CarbonImmutable::parse('2026-11-20 12:00'));
    }

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    private function spend(User $user, float $cost, ?string $at = null, string $category = UsageRecord::CATEGORY_IMAGE): void
    {
        $record = UsageRecord::create(['user_id' => $user->id, 'category' => $category, 'cost' => $cost]);

        if ($at) {
            $record->forceFill(['created_at' => CarbonImmutable::parse($at)])->save();
        }
    }

    /** Подписка, оплаченная в указанный день. */
    private function subscriber(string $plan, string $paidAt): User
    {
        $user = User::factory()->create(['plan' => $plan]);

        Subscription::create([
            'user_id' => $user->id,
            'plan' => $plan,
            'period' => Subscription::PERIOD_YEARLY,
            'status' => Subscription::STATUS_ACTIVE,
            'provider' => 'manual',
            'amount' => 0,
            'currency' => 'RUB',
            'starts_at' => CarbonImmutable::parse($paidAt),
            'ends_at' => CarbonImmutable::parse($paidAt)->addYear(),
        ]);

        return $user;
    }

    public function test_free_plan_counts_the_calendar_month(): void
    {
        $user = User::factory()->create();
        $allowance = $user->planUsage()['allowance'];

        $this->spend($user, $allowance * 0.1, '2026-11-02 10:00');
        $this->spend($user, $allowance * 0.9, '2026-10-30 10:00');

        $usage = $this->meter->snapshot($user);

        $this->assertSame(10, $usage->percentUsed());
        $this->assertSame('1 Dec', $usage->toArray()['renewsAt']);
    }

    /** У подписчика месяц считается от дня оплаты. */
    public function test_subscription_month_starts_on_the_payment_day(): void
    {
        $user = $this->subscriber('pro', '2026-08-15 09:00');

        $this->spend($user, 1.0, '2026-11-14 23:00');
        $this->spend($user, 3.0, '2026-11-16 08:00');

        $usage = $this->meter->snapshot($user);

        $this->assertSame('2026-11-15', $usage->periodStart->toDateString());
        $this->assertSame('15 Dec', $usage->toArray()['renewsAt']);
        $this->assertEqualsWithDelta(3.0, $usage->spent, 0.0001);
        $this->assertSame(50, $usage->percentUsed());
    }

    /** 31-е число не сползает к 28-му после февраля. */
    public function test_month_end_anchor_does_not_drift(): void
    {
        $this->travelTo(CarbonImmutable::parse('2027-03-31 12:00'));

        $user = $this->subscriber('pro', '2027-01-31 10:00');

        $this->assertSame('2027-03-31', $this->meter->snapshot($user)->periodStart->toDateString());
    }

    public function test_unused_allowance_carries_over_on_pro_plus(): void
    {
        $user = $this->subscriber('pro_plus', '2026-09-20 00:00');
        $base = $user->planUsage()['allowance'];

        // В прошлом месяце потрачена четверть: три четверти переходят.
        $this->spend($user, $base / 4, '2026-11-01 10:00');

        $usage = $this->meter->snapshot($user);

        $this->assertEqualsWithDelta($base * 0.75, $usage->carried, 0.0001);
        $this->assertSame(75, $usage->toArray()['carriedPercent']);
    }

    public function test_carry_over_is_capped_by_the_plan(): void
    {
        $user = $this->subscriber('pro_plus', '2026-05-20 00:00');
        $base = $user->planUsage()['allowance'];

        // Полгода без трат: копится не больше одного месяца тарифа.
        $this->assertEqualsWithDelta($base, $this->meter->snapshot($user)->carried, 0.0001);
    }

    public function test_pro_does_not_carry_over(): void
    {
        $user = $this->subscriber('pro', '2026-09-20 00:00');

        $this->assertSame(0.0, $this->meter->snapshot($user)->carried);
    }

    public function test_tiny_spend_shows_at_least_one_percent(): void
    {
        $user = $this->subscriber('max', '2026-11-20 00:00');

        $this->spend($user, 0.0001);

        $this->assertSame(1, $this->meter->snapshot($user)->percentUsed());
    }

    public function test_spent_allowance_is_refused_with_the_renewal_date(): void
    {
        $user = $this->subscriber('pro', '2026-11-03 00:00');

        $this->spend($user, $user->planUsage()['allowance']);

        $this->assertFalse($this->meter->allows($user));
        $this->assertStringContainsString('3 Dec', $this->meter->denial($user));
    }

    /** Бесплатный тариф не выбирает месячный бюджет за один день. */
    public function test_free_plan_has_a_daily_share(): void
    {
        $user = User::factory()->create();
        $plan = $user->planUsage();

        $this->spend($user, $plan['allowance'] * $plan['daily_share']);

        $this->assertStringContainsString('today', $this->meter->denial($user));

        $this->travelTo(CarbonImmutable::parse('2026-11-21 08:00'));

        $this->assertTrue($this->meter->allows($user));
    }

    public function test_interface_gets_percentages_not_dollars(): void
    {
        $user = User::factory()->create();
        $this->spend($user, 0.05, category: UsageRecord::CATEGORY_CHAT);

        $this->actingAs($user)
            ->get('/')
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.usage.percentUsed', 5)
                ->where('auth.user.usage.breakdown.chat', 5)
                ->missing('auth.user.usage.spent')
                ->missing('auth.user.usage.available'));
    }

    public function test_spent_allowance_stops_the_chat(): void
    {
        $user = User::factory()->create();
        $this->spend($user, $user->planUsage()['allowance']);

        $this->actingAs($user)
            ->post('/chats', ['message' => 'Привет'])
            ->assertSessionHas('error');

        $this->assertSame(0, $user->chats()->count());
    }

    public function test_spent_allowance_stops_the_studio(): void
    {
        $this->app->instance(ImageGenerator::class, new FakeImageGenerator);

        $user = User::factory()->create();
        $this->spend($user, $user->planUsage()['allowance']);

        $this->actingAs($user)
            ->post('/studio', ['prompt' => 'Tree', 'model' => 'fast', 'aspect_ratio' => '1:1'])
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'allowance'));

        $this->assertSame(0, Generation::count());

        $this->actingAs($user)
            ->get('/studio')
            ->assertInertia(fn ($page) => $page
                ->where('limit.percentLeft', 0)
                ->where('limit.exhausted', true));
    }
}
