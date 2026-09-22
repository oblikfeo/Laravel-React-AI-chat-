<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AuthPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_page_opens_in_login_mode_by_default(): void
    {
        $this->get('/auth')->assertOk()->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Auth/Index')
                ->where('mode', 'login')
        );
    }

    public function test_auth_page_respects_mode_from_query(): void
    {
        foreach (['register', 'forgot'] as $mode) {
            $this->get("/auth?mode={$mode}")->assertInertia(
                fn (AssertableInertia $page) => $page->where('mode', $mode)
            );
        }
    }

    public function test_unknown_mode_falls_back_to_login(): void
    {
        $this->get('/auth?mode=nonsense')->assertInertia(
            fn (AssertableInertia $page) => $page->where('mode', 'login')
        );
    }

    public function test_registration_rejects_short_password(): void
    {
        $this->post('/register', [
            'name' => 'Иван',
            'email' => 'ivan@example.com',
            'password' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        $user = User::factory()->create();

        $this->post('/register', [
            'name' => 'Иван',
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertSessionHasErrors('email');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_can_log_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }
}
