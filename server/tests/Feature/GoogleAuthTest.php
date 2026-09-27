<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_button_is_hidden_without_credentials(): void
    {
        config(['services.google.client_id' => null]);
        config(['services.google.client_secret' => null]);

        $this->get('/auth')
            ->assertInertia(fn ($page) => $page->where('googleReady', false));
    }

    public function test_button_appears_once_credentials_are_set(): void
    {
        config(['services.google.client_id' => 'test-id']);
        config(['services.google.client_secret' => 'test-secret']);

        $this->get('/auth')
            ->assertInertia(fn ($page) => $page->where('googleReady', true));
    }

    /**
     * Без ключей переход не должен вести на страницу с ошибкой.
     */
    public function test_sign_in_is_refused_without_credentials(): void
    {
        config(['services.google.client_id' => null]);
        config(['services.google.client_secret' => null]);

        $this->get('/auth/google')->assertRedirect();
    }

    public function test_sign_in_redirects_to_google(): void
    {
        config([
            'services.google.client_id' => 'test-id',
            'services.google.client_secret' => 'test-secret',
            'services.google.redirect' => 'https://example.com/auth/google/callback',
        ]);

        $response = $this->get('/auth/google');

        $response->assertRedirectContains('accounts.google.com');
    }

    /**
     * Пароль перестал быть обязательным: у пришедших через Google
     * его нет.
     */
    public function test_user_can_exist_without_a_password(): void
    {
        $user = User::create([
            'name' => 'Google User',
            'email' => 'google@example.com',
            'google_id' => '123456',
            'password' => null,
        ]);

        $this->assertNull($user->password);
        $this->assertSame('123456', $user->google_id);
    }
}
