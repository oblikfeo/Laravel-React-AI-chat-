<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\Guest;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GuestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['ai.providers.openrouter.api_key' => 'test-key']);

        Http::fake([
            '*/chat/completions' => Http::response([
                'model' => 'test',
                'choices' => [['message' => ['content' => 'Reply.']]],
            ]),
        ]);
    }

    public function test_guest_can_start_a_chat_without_signing_up(): void
    {
        $this->post('/chats', ['message' => 'Hello'])->assertRedirect();

        $chat = Chat::first();

        $this->assertNotNull($chat);
        $this->assertNull($chat->user_id);
        $this->assertNotNull($chat->guest_id);
    }

    public function test_guest_is_recognised_by_cookie(): void
    {
        $this->post('/chats', ['message' => 'First']);

        $token = Guest::first()->token;

        $this->withCookie(config('guests.cookie_name'), $token)
            ->post('/chats', ['message' => 'Second']);

        $this->assertSame(1, Guest::count());
        $this->assertSame(2, Chat::count());
    }

    /**
     * Куки могли очистить — тогда узнаём по отпечатку браузера.
     */
    public function test_guest_is_recognised_by_fingerprint_without_cookie(): void
    {
        $print = 'screen|timezone|language|cores';

        $this->withHeader('X-Guest-Fingerprint', $print)
            ->post('/chats', ['message' => 'First']);

        $this->withHeader('X-Guest-Fingerprint', $print)
            ->post('/chats', ['message' => 'Second']);

        $this->assertSame(1, Guest::count());
    }

    /**
     * Разные люди за одним IP не должны считаться одним человеком:
     * за адресом стоит весь дом, офис или кофейня.
     */
    public function test_different_browsers_on_one_ip_are_different_guests(): void
    {
        $this->withHeader('X-Guest-Fingerprint', 'desktop|1920x1080|ru')
            ->post('/chats', ['message' => 'From desktop']);

        $this->withHeader('X-Guest-Fingerprint', 'phone|390x844|ru')
            ->post('/chats', ['message' => 'From phone']);

        $this->assertSame(2, Guest::count());
    }

    public function test_guest_sees_only_their_own_chats(): void
    {
        $mine = Chat::factory()->create([
            'user_id' => null,
            'guest_id' => Guest::create(['token' => 'mine'])->id,
        ]);

        $other = Chat::factory()->create([
            'user_id' => null,
            'guest_id' => Guest::create(['token' => 'other'])->id,
        ]);

        $this->withCookie(config('guests.cookie_name'), 'mine')
            ->get("/chats/{$mine->id}")
            ->assertOk();

        $this->withCookie(config('guests.cookie_name'), 'mine')
            ->get("/chats/{$other->id}")
            ->assertForbidden();
    }

    public function test_daily_limit_stops_the_guest(): void
    {
        config(['guests.daily_messages' => 2]);

        $this->post('/chats', ['message' => 'One']);
        $token = Guest::first()->token;
        $chat = Chat::first();

        $this->withCookie(config('guests.cookie_name'), $token)
            ->post("/chats/{$chat->id}/messages", ['message' => 'Two']);

        $this->withCookie(config('guests.cookie_name'), $token)
            ->post("/chats/{$chat->id}/messages", ['message' => 'Three'])
            ->assertSessionHas('error');

        $this->assertSame(
            2,
            $chat->messages()->where('role', Message::ROLE_USER)->count(),
        );
    }

    public function test_guest_gets_only_the_free_model(): void
    {
        $this->post('/chats', ['message' => 'Hi', 'model' => 'uncensored']);

        $this->assertSame(config('guests.model'), Chat::first()->model_key);
    }

    public function test_guest_cannot_switch_the_model(): void
    {
        $this->post('/chats', ['message' => 'Hi']);
        $token = Guest::first()->token;
        $chat = Chat::first();

        $this->withCookie(config('guests.cookie_name'), $token)
            ->put("/chats/{$chat->id}/model", ['model' => 'uncensored']);

        $this->assertSame(config('guests.model'), $chat->fresh()->model_key);
    }

    /**
     * Ради переписки человек и регистрируется: терять её нельзя.
     */
    public function test_chats_move_into_the_account_after_signing_up(): void
    {
        $this->post('/chats', ['message' => 'Before signing up']);
        $token = Guest::first()->token;

        $this->withCookie(config('guests.cookie_name'), $token)
            ->post('/register', [
                'name' => 'New User',
                'email' => 'new@example.com',
                'password' => 'password123',
            ]);

        $user = User::where('email', 'new@example.com')->first();

        $this->assertSame(1, $user->chats()->count());
        $this->assertSame('Before signing up', $user->chats()->first()->title);
    }

    public function test_claimed_chat_is_no_longer_available_to_the_guest(): void
    {
        $this->post('/chats', ['message' => 'Mine']);
        $token = Guest::first()->token;
        $chat = Chat::first();

        $this->withCookie(config('guests.cookie_name'), $token)
            ->post('/register', [
                'name' => 'Owner',
                'email' => 'owner@example.com',
                'password' => 'password123',
            ]);

        $this->post('/logout');

        $this->withCookie(config('guests.cookie_name'), $token)
            ->get("/chats/{$chat->id}")
            ->assertForbidden();
    }

    public function test_guest_state_reaches_the_interface(): void
    {
        config(['guests.daily_messages' => 5]);

        $this->get('/')->assertInertia(fn ($page) => $page
            ->where('guest.limit', 5)
            ->where('guest.remaining', 5)
        );
    }

    public function test_signed_in_user_has_no_guest_limits(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertInertia(fn ($page) => $page->where('guest', null));
    }

    public function test_ip_cap_stops_mass_abuse(): void
    {
        // Потолок по адресу намеренно выше личного лимита: он ловит
        // накрутку через режим инкогнито, а не обычных посетителей.
        config(['guests.daily_messages' => 10, 'guests.daily_messages_per_ip' => 2]);

        $this->withHeader('X-Guest-Fingerprint', 'browser-one')
            ->post('/chats', ['message' => 'One']);

        $this->withHeader('X-Guest-Fingerprint', 'browser-two')
            ->post('/chats', ['message' => 'Two']);

        $this->withHeader('X-Guest-Fingerprint', 'browser-three')
            ->post('/chats', ['message' => 'Three'])
            ->assertSessionHas('error');

        $this->assertSame(2, Chat::count());
    }
}
