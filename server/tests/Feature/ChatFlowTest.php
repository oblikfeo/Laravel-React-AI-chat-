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

    /**
     * Создание чата не ждёт модель: человек переходит в диалог сразу,
     * а ответ запрашивается уже оттуда.
     */
    public function test_creating_chat_stores_the_question_only(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/chats', ['message' => 'Привет, как дела?'])
            ->assertRedirect();

        $chat = Chat::first();

        $this->assertNotNull($chat);
        $this->assertSame($user->id, $chat->user_id);
        $this->assertSame(1, $chat->messages()->count());
        $this->assertTrue($chat->messages()->where('role', Message::ROLE_USER)->exists());
        $this->assertFalse($chat->messages()->where('role', Message::ROLE_ASSISTANT)->exists());
    }

    public function test_opening_the_chat_marks_the_reply_as_awaited(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/chats', ['message' => 'Вопрос']);

        $this->actingAs($user)
            ->get('/chats/'.Chat::first()->id)
            ->assertInertia(fn ($page) => $page->where('awaitingReply', true));
    }

    public function test_reply_request_adds_the_answer(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/chats', ['message' => 'Вопрос']);
        $chat = Chat::first();

        $this->actingAs($user)
            ->post("/chats/{$chat->id}/reply")
            ->assertRedirect();

        $this->assertSame(2, $chat->messages()->count());
        $this->assertTrue($chat->messages()->where('role', Message::ROLE_ASSISTANT)->exists());
    }

    /**
     * Запрос ответа повторяется при обновлении страницы: второй ответ
     * на то же сообщение появиться не должен.
     */
    public function test_repeated_reply_request_adds_nothing(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/chats', ['message' => 'Вопрос']);
        $chat = Chat::first();

        $this->actingAs($user)->post("/chats/{$chat->id}/reply");
        $this->actingAs($user)->post("/chats/{$chat->id}/reply");

        $this->assertSame(2, $chat->messages()->count());
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
