<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\UsageRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Админка: пускает только администраторов и показывает деньги,
 * подписки, модели и расходы.
 */
class AdminTest extends TestCase
{
    use RefreshDatabase;

    private const PAGES = ['/admin', '/admin/models', '/admin/subscriptions', '/admin/payments', '/admin/users'];

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    /** Подписчик с оплаченным платежом и тратами. */
    private function customer(): User
    {
        $user = User::factory()->create(['plan' => 'pro', 'name' => 'Paying Customer']);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan' => 'pro',
            'period' => Subscription::PERIOD_MONTHLY,
            'status' => Subscription::STATUS_ACTIVE,
            'provider' => 'yookassa',
            'amount' => 1800,
            'currency' => 'RUB',
            'starts_at' => now(),
            'ends_at' => now()->addDays(27),
        ]);

        Payment::create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'plan' => 'pro',
            'period' => Subscription::PERIOD_MONTHLY,
            'status' => Payment::STATUS_SUCCEEDED,
            'amount' => 1800,
            'currency' => 'RUB',
            'provider' => 'yookassa',
            'provider_id' => 'pay-1',
            'paid_at' => now(),
        ]);

        UsageRecord::create([
            'user_id' => $user->id,
            'category' => UsageRecord::CATEGORY_IMAGE,
            'model_key' => 'fast',
            'provider_model' => config('studio.models.fast.provider_model'),
            'cost' => 1.5,
        ]);

        return $user;
    }

    public function test_guest_cannot_open_the_admin(): void
    {
        foreach (self::PAGES as $page) {
            $this->get($page)->assertRedirect();
        }
    }

    /** Обычному пользователю админки как будто нет. */
    public function test_regular_user_gets_not_found(): void
    {
        $user = User::factory()->create(['plan' => 'max']);

        foreach (self::PAGES as $page) {
            $this->actingAs($user)->get($page)->assertNotFound();
        }
    }

    public function test_admin_opens_every_page(): void
    {
        $admin = $this->admin();
        $this->customer();

        foreach (self::PAGES as $page) {
            $this->actingAs($admin)->get($page)->assertOk();
        }
    }

    public function test_overview_shows_revenue_and_costs(): void
    {
        $this->customer();

        $this->actingAs($this->admin())
            ->get('/admin')
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Overview')
                ->where('overview.revenue.month.0.amount', 1800)
                ->where('overview.revenue.month.0.currency', 'RUB')
                ->where('overview.subscriptions.active', 1)
                ->where('overview.subscriptions.monthly.0.amount', 1800)
                ->where('overview.spend.month', 1.5)
                ->where('overview.spend.byCategory.0.category', 'image')
                ->where('overview.spend.topUsers.0.name', 'Paying Customer')
                ->has('overview.spend.daily', 30));
    }

    /** Модели с настоящими названиями у провайдера — только здесь. */
    public function test_models_page_lists_provider_models_and_spend(): void
    {
        $this->customer();

        $this->actingAs($this->admin())
            ->get('/admin/models')
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Models')
                ->where('sections.0.title', 'Chat')
                ->where('sections.0.rows.0.providerModel', config('models.list.auto.provider_model'))
                ->where('sections.2.title', 'Studio · Images')
                ->where('sections.2.rows.0.key', 'fast')
                ->where('sections.2.rows.0.requests', 1)
                ->where('sections.2.rows.0.spend', 1.5));
    }

    public function test_subscriptions_show_usage_of_the_plan(): void
    {
        $this->customer();

        $this->actingAs($this->admin())
            ->get('/admin/subscriptions?status=active')
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Subscriptions')
                ->where('subscriptions.data.0.plan', 'Pro')
                ->where('subscriptions.data.0.usage.percentUsed', 25)
                ->where('subscriptions.data.0.usage.spent', 1.5));
    }

    public function test_payments_show_history_and_top_payers(): void
    {
        $this->customer();

        $this->actingAs($this->admin())
            ->get('/admin/payments?status=succeeded')
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Payments')
                ->where('payments.data.0.amount', 1800)
                ->where('payments.data.0.user.name', 'Paying Customer')
                ->where('topPayers.0.amount', 1800));
    }

    public function test_users_are_searchable_and_show_what_they_paid(): void
    {
        $this->customer();
        User::factory()->create(['email' => 'someone@else.test']);

        $this->actingAs($this->admin())
            ->get('/admin/users?q=Paying')
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Users')
                ->has('users.data', 1)
                ->where('users.data.0.paid', 1800));
    }

    public function test_user_page_shows_everything_for_support(): void
    {
        $customer = $this->customer();

        $this->actingAs($this->admin())
            ->get("/admin/users/{$customer->id}")
            ->assertInertia(fn ($page) => $page
                ->component('Admin/User')
                ->where('usage.percentUsed', 25)
                ->has('subscriptions', 1)
                ->has('payments', 1)
                ->where('records.0.cost', 1.5));
    }

    public function test_menu_knows_who_is_admin(): void
    {
        $this->actingAs($this->admin())
            ->get('/')
            ->assertInertia(fn ($page) => $page->where('auth.user.isAdmin', true));

        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertInertia(fn ($page) => $page->where('auth.user.isAdmin', false));
    }

    public function test_admin_access_is_granted_from_the_console(): void
    {
        $user = User::factory()->create(['email' => 'boss@uncensia.test']);

        $this->artisan('user:admin', ['email' => 'boss@uncensia.test'])->assertSuccessful();
        $this->assertTrue($user->fresh()->isAdmin());

        $this->artisan('user:admin', ['email' => 'boss@uncensia.test', '--revoke' => true])->assertSuccessful();
        $this->assertFalse($user->fresh()->isAdmin());
    }
}
