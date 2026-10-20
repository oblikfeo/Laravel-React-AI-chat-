<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\Generation;
use App\Models\Message;
use App\Models\User;
use App\Services\Ai\AiChatProvider;
use App\Services\Ai\AiResponse;
use App\Services\Ai\ModelCatalog;
use App\Services\Chat\Routing\RequestRouter;
use App\Services\Chat\Routing\Route;
use App\Services\Chat\Routing\RoutingInput;
use App\Services\Studio\ImageGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeImageGenerator;
use Tests\TestCase;

/**
 * Диспетчер чата: модель решает, что человек хочет, а чат исполняет.
 *
 * Здесь диспетчер подменён: проверяется, как чат поступает с его
 * решением. Качество самих решений проверяет chat:evaluate-routing на
 * настоящей модели.
 */
class ChatRoutingTest extends TestCase
{
    use RefreshDatabase;

    private FakeImageGenerator $images;

    /** Что вернёт диспетчер; null — диспетчер молчит. */
    public ?Route $decision = null;

    /** Что видел диспетчер. */
    public ?RoutingInput $seen = null;

    /** С какой моделью спросили текстовую модель и что она ответит. */
    public ?string $askedModel = null;

    public string $answer = 'A text answer';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->images = new FakeImageGenerator;
        $this->app->instance(ImageGenerator::class, $this->images);

        $test = $this;

        $this->app->instance(RequestRouter::class, new class($test) implements RequestRouter
        {
            public function __construct(private readonly ChatRoutingTest $test) {}

            public function route(RoutingInput $input): ?Route
            {
                $this->test->seen = $input;

                return $this->test->decision;
            }
        });

        $this->app->instance(AiChatProvider::class, new class($test) implements AiChatProvider
        {
            public function __construct(private readonly ChatRoutingTest $test) {}

            public function isConfigured(): bool
            {
                return true;
            }

            public function complete(array $messages, ?string $model = null, array $options = []): AiResponse
            {
                if (str_contains($messages[0]['content'] ?? '', 'Uncensia')) {
                    $this->test->askedModel = $model;

                    return new AiResponse(content: $this->test->answer, model: 'test-model');
                }

                return new AiResponse(content: 'translated', model: 'test-model');
            }
        });
    }

    private function say(User $user, string $text, ?Chat $chat = null, string $model = 'auto'): Chat
    {
        if (! $chat) {
            $this->actingAs($user)->post('/chats', ['message' => $text, 'model' => $model]);
            $chat = Chat::latest('id')->first();
        } else {
            $this->actingAs($user)->post("/chats/{$chat->id}/messages", ['message' => $text]);
        }

        $this->actingAs($user)->post("/chats/{$chat->id}/reply");

        return $chat;
    }

    private function lastReply(Chat $chat): Message
    {
        return $chat->messages()->where('role', Message::ROLE_ASSISTANT)->latest('id')->first();
    }

    /** Тот самый случай: «создай кота на траве» — картинка. */
    public function test_dispatcher_decides_on_a_picture(): void
    {
        $this->decision = new Route('image', 'кот на траве', 0.95);

        $chat = $this->say(User::factory()->create(), 'создай кота на траве');

        $reply = $this->lastReply($chat);

        $this->assertNotNull($reply->generation_id);
        $this->assertSame('кот на траве', $reply->generation->prompt);
        $this->assertNull($this->askedModel);
    }

    public function test_decision_is_stored_once_with_the_message(): void
    {
        $this->decision = new Route('video', 'кот на пляже', 0.9);

        $chat = $this->say(User::factory()->create(), 'хочу видео с котом на пляже');

        $question = $chat->messages()->where('role', Message::ROLE_USER)->first();

        $this->assertSame('video', $question->routing['intent']);
        $this->assertSame('video', $question->intent);
        $this->assertSame('video', $this->lastReply($chat)->studio_link['tab']);
    }

    public function test_dispatcher_sees_the_conversation(): void
    {
        $user = User::factory()->create();

        $this->decision = new Route('image', 'собака', 0.95);
        $chat = $this->say($user, 'нарисуй собаку');

        $this->decision = new Route('remove_background', '', 0.95);
        $this->say($user, 'а теперь без фона', $chat);

        $this->assertTrue($this->seen->previousReplyWasImage);
        $this->assertCount(2, $this->seen->recent);
        $this->assertTrue($this->images->backgroundRemoved);
    }

    /** Диспетчер молчит — решает запасное правило, чат не ломается. */
    public function test_rules_take_over_when_the_dispatcher_is_silent(): void
    {
        $this->decision = null;

        $chat = $this->say(User::factory()->create(), 'нарисуй собаку');

        $this->assertNotNull($this->lastReply($chat)->generation_id);
        $this->assertSame('rules', $chat->messages()->first()->routing['source']);
    }

    /** Неуверенный диспетчер картинку не рисует: отвечает текст. */
    public function test_unsure_decision_gets_a_text_answer(): void
    {
        $this->decision = new Route('image', 'что-то', 0.3);

        $chat = $this->say(User::factory()->create(), 'интересно, как выглядел бы кот-космонавт');

        $this->assertNull($this->lastReply($chat)->generation_id);
        $this->assertNull($this->images->lastGeneration);
        $this->assertSame('A text answer', $this->lastReply($chat)->content);
    }

    /** Править нечего — ошибка диспетчера не запускает правку. */
    public function test_edit_without_a_picture_gets_a_text_answer(): void
    {
        $this->decision = new Route('remove_background', '', 0.9);

        $chat = $this->say(User::factory()->create(), 'убери лишнее из текста');

        $this->assertNull($this->lastReply($chat)->generation_id);
        $this->assertFalse($this->images->backgroundRemoved);
    }

    /** На Auto диспетчер подбирает модель под задачу. */
    public function test_auto_chat_gets_the_suggested_model(): void
    {
        $this->decision = Route::chat('smart');

        $this->say(User::factory()->create(), 'докажи, что корень из двух иррационален');

        $this->assertSame(ModelCatalog::providerModel('smart'), $this->askedModel);
    }

    /** Модель, выбранную человеком, диспетчер не меняет. */
    public function test_chosen_model_is_respected(): void
    {
        $this->decision = Route::chat('smart');

        $this->say(User::factory()->create(), 'докажи теорему', model: 'fast');

        $this->assertSame(ModelCatalog::providerModel('fast'), $this->askedModel);
    }

    /** Если модель всё же изобразила картинку текстом — рисуем настоящую. */
    public function test_pretended_picture_becomes_a_real_one(): void
    {
        $this->decision = Route::chat();
        $this->answer = '[Image shown to the user: кот на траве]';

        $chat = $this->say(User::factory()->create(), 'кот на траве пожалуйста');

        $reply = $this->lastReply($chat);

        $this->assertNotNull($reply->generation_id);
        $this->assertSame(0, Message::where('content', 'like', '%Image shown%')->count());
        $this->assertSame(Generation::STATUS_READY, $reply->generation->status);
    }
}
