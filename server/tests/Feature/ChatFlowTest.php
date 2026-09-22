<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_uses_three_fields_without_confirmation(): void
    {
        $response = $this->post('/register', [
            'name' => 'Иван',
            'email' => 'ivan@example.com',
            'password' => 'secret-password',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'ivan@example.com']);
    }

    public function test_login_works_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret-password')]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_creating_chat_stores_question_and_reply(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/chats', ['message' => 'Привет, как дела?'])
            ->assertRedirect();

        $chat = Chat::first();

        $this->assertNotNull($chat);
        $this->assertSame($user->id, $chat->user_id);
        $this->assertSame(2, $chat->messages()->count());
        $this->assertTrue($chat->messages()->where('role', Message::ROLE_USER)->exists());
        $this->assertTrue($chat->messages()->where('role', Message::ROLE_ASSISTANT)->exists());
    }

    public function test_chat_of_another_user_is_forbidden(): void
    {
        $chat = Chat::factory()->for(User::factory())->create();

        $this->actingAs(User::factory()->create())
            ->get("/chats/{$chat->id}")
            ->assertForbidden();
    }

    public function test_guest_cannot_create_chat(): void
    {
        $this->post('/chats', ['message' => 'Привет'])
            ->assertRedirect('/auth');
    }
}
