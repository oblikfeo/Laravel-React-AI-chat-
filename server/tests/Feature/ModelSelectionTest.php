<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\User;
use App\Services\Ai\ModelCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModelSelectionTest extends TestCase
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

    /**
     * Настоящий PNG минимального размера.
     *
     * UploadedFile::fake()->image() требует расширение GD, которого
     * в этой среде нет, а проверка типа файла смотрит на содержимое.
     */
    private function pngFile(string $name = 'photo.png'): UploadedFile
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        );

        $path = tempnam(sys_get_temp_dir(), 'png').'.png';
        file_put_contents($path, $png);

        return new UploadedFile($path, $name, 'image/png', null, true);
    }

    public function test_unknown_model_falls_back_to_the_default(): void
    {
        $this->assertSame(config('models.default'), ModelCatalog::resolve('nonsense'));
        $this->assertSame(config('models.default'), ModelCatalog::resolve(null));
    }

    public function test_chat_remembers_the_chosen_model(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/chats', ['message' => 'Hi', 'model' => 'smart'])
            ->assertRedirect();

        $this->assertSame('smart', $user->chats()->latest('id')->first()->model_key);
    }

    public function test_model_can_be_changed_in_an_open_chat(): void
    {
        $user = User::factory()->create();
        $chat = Chat::factory()->for($user)->create(['model_key' => 'auto']);

        $this->actingAs($user)
            ->put("/chats/{$chat->id}/model", ['model' => 'roleplay'])
            ->assertSessionHasNoErrors();

        $this->assertSame('roleplay', $chat->fresh()->model_key);
    }

    public function test_unknown_model_is_rejected(): void
    {
        $user = User::factory()->create();
        $chat = Chat::factory()->for($user)->create();

        $this->actingAs($user)
            ->put("/chats/{$chat->id}/model", ['model' => 'nonsense'])
            ->assertSessionHasErrors('model');
    }

    /**
     * Картинку понимает не каждая модель. Если выбрана текстовая,
     * запрос должен уйти той, что умеет, иначе модель ответит,
     * что не работает с файлами.
     */
    public function test_image_switches_to_a_model_that_understands_it(): void
    {
        $this->assertFalse(ModelCatalog::supportsVision('smart'));

        $chosen = ModelCatalog::forRequest('smart', hasImages: true);

        $this->assertTrue(ModelCatalog::supportsVision($chosen));
    }

    public function test_text_request_keeps_the_chosen_model(): void
    {
        $this->assertSame('smart', ModelCatalog::forRequest('smart', hasImages: false));
    }

    public function test_provider_model_is_never_exposed_to_the_interface(): void
    {
        $list = ModelCatalog::forInterface();

        $this->assertNotEmpty($list);

        foreach ($list as $model) {
            $this->assertArrayNotHasKey('provider_model', $model);

            foreach ($model as $value) {
                $this->assertStringNotContainsString('/', (string) $value);
            }
        }
    }

    public function test_attached_document_reaches_the_model(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $this->actingAs($user)->post('/chats', [
            'message' => 'Summarise this',
            'files' => [
                UploadedFile::fake()->createWithContent('notes.txt', 'SECRET-MARKER-42'),
            ],
        ])->assertRedirect();

        // Ответ запрашивается отдельно, из открытого диалога.
        $chat = $user->chats()->latest('id')->first();
        $this->actingAs($user)->post("/chats/{$chat->id}/reply");

        // Текст документа должен уйти в запросе: модель не открывает
        // файлы сама, она видит только то, что ей прислали.
        Http::assertSent(function ($request) {
            return str_contains(json_encode($request->data()), 'SECRET-MARKER-42');
        });
    }

    public function test_attachment_is_stored_and_linked(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $this->actingAs($user)->post('/chats', [
            'message' => 'Look',
            'files' => [$this->pngFile()],
        ]);

        $message = $user->chats()->latest('id')->first()
            ->messages()->where('role', 'user')->first();

        $this->assertCount(1, $message->attachments);
        $this->assertSame('photo.png', $message->attachments->first()->name);
        Storage::disk('local')->assertExists($message->attachments->first()->path);
    }

    public function test_message_can_be_just_a_file(): void
    {
        Storage::fake('local');

        $this->actingAs(User::factory()->create())
            ->post('/chats', [
                'files' => [$this->pngFile()],
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_empty_request_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/chats', [])
            ->assertSessionHasErrors('message');
    }

    public function test_dangerous_file_type_is_rejected(): void
    {
        Storage::fake('local');

        $this->actingAs(User::factory()->create())
            ->post('/chats', [
                'message' => 'Run this',
                'files' => [UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')],
            ])
            ->assertSessionHasErrors('files.0');
    }

    public function test_attachment_of_another_user_is_not_served(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $this->actingAs($owner)->post('/chats', [
            'message' => 'Mine',
            'files' => [$this->pngFile('private.png')],
        ]);

        $attachment = $owner->chats()->latest('id')->first()
            ->messages()->where('role', 'user')->first()
            ->attachments->first();

        $this->actingAs(User::factory()->create())
            ->get("/attachments/{$attachment->id}")
            ->assertForbidden();
    }

    public function test_model_change_leaves_a_mark_in_the_chat(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/chats', ['message' => 'Hi', 'model' => 'auto']);
        $chat = $user->chats()->latest('id')->first();

        $this->actingAs($user)->put("/chats/{$chat->id}/model", ['model' => 'smart']);

        $mark = $chat->messages()->where('role', 'system')->first();

        $this->assertNotNull($mark);
        $this->assertSame('Smart', $mark->content);
    }

    public function test_choosing_the_same_model_leaves_no_mark(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/chats', ['message' => 'Hi', 'model' => 'auto']);
        $chat = $user->chats()->latest('id')->first();

        $this->actingAs($user)->put("/chats/{$chat->id}/model", ['model' => 'auto']);

        $this->assertSame(0, $chat->messages()->where('role', 'system')->count());
    }

    /**
     * Отметка — для человека. Модели она не отправляется, иначе та
     * примет её за указание.
     */
    public function test_mark_is_not_sent_to_the_model(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/chats', ['message' => 'Hi', 'model' => 'auto']);
        $chat = $user->chats()->latest('id')->first();

        $this->actingAs($user)->put("/chats/{$chat->id}/model", ['model' => 'smart']);
        $this->actingAs($user)->post("/chats/{$chat->id}/messages", ['message' => 'Again']);
        $this->actingAs($user)->post("/chats/{$chat->id}/reply");

        Http::assertSent(function ($request) {
            foreach ($request->data()['messages'] as $message) {
                if (($message['role'] ?? '') === 'system'
                    && $message['content'] === 'Smart') {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Отметка не меняет того, чья очередь говорить.
     */
    public function test_mark_does_not_trigger_a_reply(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/chats', ['message' => 'Hi']);
        $chat = $user->chats()->latest('id')->first();
        $this->actingAs($user)->post("/chats/{$chat->id}/reply");

        $this->actingAs($user)->put("/chats/{$chat->id}/model", ['model' => 'smart']);

        $this->actingAs($user)
            ->get("/chats/{$chat->id}")
            ->assertInertia(fn ($page) => $page->where('awaitingReply', false));
    }
}
