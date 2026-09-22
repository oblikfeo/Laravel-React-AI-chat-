<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_starts_on_the_free_plan(): void
    {
        $user = User::factory()->create();

        $this->assertSame('free', $user->plan);
        $this->assertSame('Free', $user->planName());
    }

    public function test_free_user_is_offered_an_upgrade(): void
    {
        $user = User::factory()->create(['plan' => 'free']);

        $this->assertTrue($user->isPromotedPlan());
    }

    public function test_paying_user_is_not_offered_an_upgrade(): void
    {
        $user = User::factory()->create(['plan' => 'pro']);

        $this->assertFalse($user->isPromotedPlan());
        $this->assertSame('Pro', $user->planName());
    }

    public function test_plan_reaches_the_interface(): void
    {
        $user = User::factory()->create(['plan' => 'pro_plus']);

        $this->actingAs($user)
            ->get('/')
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.plan', 'Pro+')
                ->where('auth.user.canUpgrade', false)
            );
    }
}
