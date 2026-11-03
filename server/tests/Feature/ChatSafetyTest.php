<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Services\Chat\ChatContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Огромный текст и тяжёлые файлы не роняют чат и не раздувают цену
 * каждого ответа.
 */
class ChatSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_too_long_message_is_refused_with_a_reason(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/chats', ['message' => str_repeat('a', 8001)])
            ->assertSessionHasErrors('message');

        $this->assertSame(0, Chat::count());
    }

    public function test_files_over_the_total_size_are_refused(): void
    {
        $files = array_map(
            fn (int $i) => UploadedFile::fake()->create("doc{$i}.txt", 9 * 1024, 'text/plain'),
            range(1, 3),
        );

        $this->actingAs(User::factory()->create())
            ->post('/chats', ['message' => 'Read these', 'files' => $files])
            ->assertSessionHasErrors('files');
    }

    public function test_unsupported_file_is_refused(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/chats', ['message' => 'Run this', 'files' => [UploadedFile::fake()->create('x.exe', 10, 'application/x-msdownload')]])
            ->assertSessionHasErrors('files.0');
    }

    /** Длинная история с документами не уходит модели целиком. */
    public function test_history_sent_to_the_model_is_capped(): void
    {
        config(['ai.context_chars' => 20000]);

        $user = User::factory()->create();
        $chat = Chat::create(['user_id' => $user->id, 'title' => 'Docs', 'model_key' => 'auto', 'last_message_at' => now()]);

        foreach (range(1, 6) as $i) {
            $message = $chat->messages()->create(['role' => Message::ROLE_USER, 'content' => "Document {$i}"]);
            $message->attachments()->create([
                'name' => "doc{$i}.txt",
                'mime' => 'text/plain',
                'size' => 40000,
                'disk' => 'local',
                'path' => "a/doc{$i}.txt",
                'extracted_text' => str_repeat("text{$i} ", 8000),
            ]);
            $chat->messages()->create(['role' => Message::ROLE_ASSISTANT, 'content' => "Read {$i}"]);
        }

        $messages = app(ChatContext::class)->for($chat->fresh());
        $sent = collect($messages)->slice(1)->sum(fn (array $turn) => mb_strlen(is_string($turn['content']) ? $turn['content'] : $turn['content'][0]['text']));

        $this->assertLessThanOrEqual(20000, $sent);
        // Новое уходит, старое отброшено.
        $this->assertStringContainsString('Read 6', json_encode($messages));
        $this->assertStringNotContainsString('Document 1', json_encode($messages));
    }

    /** Картинки уходят только самые свежие. */
    public function test_only_recent_images_are_sent(): void
    {
        config(['ai.context_images' => 2]);

        $user = User::factory()->create();
        $chat = Chat::create(['user_id' => $user->id, 'title' => 'Pics', 'model_key' => 'auto', 'last_message_at' => now()]);
        Storage::disk('local')->put('p.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));

        foreach (range(1, 3) as $i) {
            $message = $chat->messages()->create(['role' => Message::ROLE_USER, 'content' => "Picture {$i}"]);
            foreach (range(1, 2) as $n) {
                $message->attachments()->create(['name' => "p{$i}{$n}.png", 'mime' => 'image/png', 'size' => 70, 'disk' => 'local', 'path' => 'p.png']);
            }
            $chat->messages()->create(['role' => Message::ROLE_ASSISTANT, 'content' => "Seen {$i}"]);
        }

        $images = collect(app(ChatContext::class)->for($chat->fresh()))
            ->filter(fn (array $turn) => is_array($turn['content']))
            ->sum(fn (array $turn) => count($turn['content']) - 1);

        $this->assertSame(2, $images);
        $this->assertSame(6, Attachment::count());
    }
}
