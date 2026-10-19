<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\Generation;
use App\Models\Message;
use App\Models\User;
use App\Services\Ai\AiChatProvider;
use App\Services\Ai\AiResponse;
use App\Services\Chat\StudioIntent;
use App\Services\Studio\ImageGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeImageGenerator;
use Tests\TestCase;

/**
 * Картинки прямо в чате.
 *
 * «Нарисуй собаку» — в ответ картинка. «Убери фон» следующим
 * сообщением — та же картинка без фона. Без слов и без перехода в
 * Студию.
 */
class ChatImagesTest extends TestCase
{
    use RefreshDatabase;

    private FakeImageGenerator $images;

    /** Сколько раз спросили текстовую модель. */
    public int $asked = 0;

    /** Что ушло текстовой модели в последний раз. */
    public array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->images = new FakeImageGenerator;
        $this->app->instance(ImageGenerator::class, $this->images);

        $this->app->instance(AiChatProvider::class, new class($this) implements AiChatProvider
        {
            public function __construct(private readonly ChatImagesTest $test) {}

            public function isConfigured(): bool
            {
                return true;
            }

            public function complete(array $messages, ?string $model = null, array $options = []): AiResponse
            {
                // Той же моделью Студия переводит описание картинки на
                // английский. Считаем только ответы в чате.
                if (str_contains($messages[0]['content'] ?? '', 'Uncensia')) {
                    $this->test->asked++;
                    $this->test->sent = $messages;
                }

                return new AiResponse(content: 'A text answer', model: 'test-model');
            }
        });
    }

    /** Пишет в чат и дожидается ответа. */
    private function say(User $user, string $text, ?Chat $chat = null, array $files = []): Chat
    {
        if (! $chat) {
            $this->actingAs($user)->post('/chats', ['message' => $text, 'files' => $files]);
            $chat = Chat::latest('id')->first();
        } else {
            $this->actingAs($user)->post("/chats/{$chat->id}/messages", ['message' => $text, 'files' => $files]);
        }

        $this->actingAs($user)->post("/chats/{$chat->id}/reply");

        return $chat;
    }

    private function lastReply(Chat $chat): Message
    {
        return $chat->messages()->where('role', Message::ROLE_ASSISTANT)->latest('id')->first();
    }

    public function test_asking_for_a_picture_returns_a_picture(): void
    {
        $chat = $this->say(User::factory()->create(), 'Нарисуй собаку на пляже');

        $reply = $this->lastReply($chat);

        $this->assertNotNull($reply->generation_id);
        $this->assertSame('', $reply->content);
        $this->assertSame(Generation::STATUS_READY, $reply->generation->status);

        // Рисовала модель картинок, текстовую не спрашивали.
        $this->assertSame(0, $this->asked);
        $this->assertSame('Нарисуй собаку на пляже', $reply->generation->prompt);
    }

    /** Картинка из чата лежит в галерее, но не в общей ленте. */
    public function test_chat_picture_is_private(): void
    {
        $chat = $this->say(User::factory()->create(), 'Draw a red fox in the snow');

        $this->assertFalse($this->lastReply($chat)->generation->is_public);
    }

    /** Нарисовать, затем убрать фон следующим сообщением. */
    public function test_follow_up_removes_the_background_of_the_last_picture(): void
    {
        $user = User::factory()->create();

        $chat = $this->say($user, 'Создай фото собаки');
        $this->say($user, 'Убери фон', $chat);

        $reply = $this->lastReply($chat);

        $this->assertTrue($this->images->backgroundRemoved);
        $this->assertSame(Generation::OP_BACKGROUND, $reply->generation->operation);
        $this->assertSame(0, $this->asked);
    }

    /** Своё фото с просьбой убрать людей — правка по описанию. */
    public function test_attached_photo_is_edited_by_description(): void
    {
        $photo = UploadedFile::fake()->createWithContent(
            'street.png',
            base64_decode(FakeImageGenerator::PIXEL),
        );

        $chat = $this->say(
            User::factory()->create(),
            'Девушку в розовой байке оставь, остальных людей с фото убери',
            files: [$photo],
        );

        $reply = $this->lastReply($chat);

        $this->assertSame(Generation::OP_EDIT, $reply->generation->operation);
        $this->assertNotNull($this->images->lastEdit);
        $this->assertStringContainsString('розовой', $reply->generation->prompt);
    }

    public function test_ordinary_questions_still_get_text(): void
    {
        $chat = $this->say(User::factory()->create(), 'Как нарисовать собаку карандашом?');

        $reply = $this->lastReply($chat);

        $this->assertNull($reply->generation_id);
        $this->assertSame('A text answer', $reply->content);
        $this->assertSame(1, $this->asked);
    }

    /** Без картинки в диалоге «убери» — обычный вопрос, а не правка. */
    public function test_edit_words_without_a_picture_go_to_the_text_model(): void
    {
        $chat = $this->say(User::factory()->create(), 'Убери лишние слова из этого текста: привет привет мир');

        $this->assertNull($this->lastReply($chat)->generation_id);
        $this->assertSame(1, $this->asked);
    }

    /** Модель знает, что было нарисовано, — и не врёт, что прислала сама. */
    public function test_text_model_knows_what_was_drawn(): void
    {
        $user = User::factory()->create();

        $chat = $this->say($user, 'Нарисуй собаку');
        $this->say($user, 'Какой породы она получилась?', $chat);

        $history = collect($this->sent)->pluck('content')->implode("\n");

        $this->assertStringContainsString('[Image shown to the user: Нарисуй собаку]', $history);
        $this->assertStringContainsString('never write that you have created', $this->sent[0]['content']);
    }

    public function test_daily_limit_is_shared_with_the_studio(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < config('studio.daily_limit_free'); $i++) {
            $this->actingAs($user)->post('/studio', ['prompt' => 'Tree', 'model' => 'fast', 'aspect_ratio' => '1:1']);
        }

        $chat = $this->say($user, 'Нарисуй собаку');

        $reply = $this->lastReply($chat);

        $this->assertNull($reply->generation_id);
        $this->assertStringContainsString('limit', $reply->content);
    }

    public function test_failed_picture_is_explained(): void
    {
        $this->app->instance(ImageGenerator::class, new FakeImageGenerator(fails: true));

        $chat = $this->say(User::factory()->create(), 'Нарисуй собаку');

        $reply = $this->lastReply($chat);

        $this->assertNull($reply->generation_id);
        $this->assertNotSame('', $reply->content);
    }

    /** В чате без цензуры и картинка без цензуры. */
    public function test_uncensored_chat_draws_with_the_uncensored_model(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/chats', ['message' => 'Нарисуй закат', 'model' => 'uncensored']);
        $chat = Chat::first();
        $this->actingAs($user)->post("/chats/{$chat->id}/reply");

        $this->assertSame('uncensored', $this->lastReply($chat)->generation->model_key);
    }

    public function test_chat_page_shows_the_picture(): void
    {
        $user = User::factory()->create();
        $chat = $this->say($user, 'Нарисуй собаку');

        $this->actingAs($user)
            ->get("/chats/{$chat->id}")
            ->assertInertia(fn ($page) => $page
                ->where('messages.1.image.prompt', 'Нарисуй собаку')
                ->has('messages.1.image.url'));
    }

    public function test_intent_examples(): void
    {
        $intent = new StudioIntent;

        $this->assertSame('image', $intent->detect('Create an image of Trump in the guise of Kim Jong Un.')['mode']);
        $this->assertSame('image', $intent->detect('нарисуй кота', 1)['mode']);
        $this->assertSame(Generation::OP_BACKGROUND, $intent->detect('remove the background', 1)['operation']);
        $this->assertSame(Generation::OP_UPSCALE, $intent->detect('увеличь качество', 1)['operation']);
        $this->assertSame(Generation::OP_EDIT, $intent->detect('сделай небо закатным', 1)['operation']);
        $this->assertSame(Generation::OP_COMBINE, $intent->detect('объедини эти фото в одно', 2)['operation']);
        $this->assertNull($intent->detect('Объясни теорию относительности', 1));
        $this->assertNull($intent->detect('удали дубликаты из списка'));
    }

    /** «Сгенерируй» — тот же глагол, что у картинок, но это музыка. */
    public function test_audio_requests_are_recognised(): void
    {
        $intent = new StudioIntent;

        foreach ([
            'сгенерируй музыку для вечеринки' => 'music',
            'создай песню про лето' => 'music',
            'generate a song about summer' => 'music',
            'озвучь: добро пожаловать' => 'speech',
            'сделай звук открывающейся двери' => 'effect',
        ] as $text => $kind) {
            $result = $intent->detect($text);

            $this->assertSame('audio', $result['mode'] ?? null, $text);
            $this->assertSame($kind, $result['operation'], $text);
        }

        $this->assertNull($intent->detect('Какая песня была самой популярной в 1999 году?'));
    }

    public function test_video_requests_are_recognised(): void
    {
        $intent = new StudioIntent;

        $this->assertSame(Generation::OP_VIDEO, $intent->detect('сгенерируй видео с котом')['operation']);
        $this->assertSame(Generation::OP_VIDEO, $intent->detect('make a video of a dog running')['operation']);
        $this->assertSame(Generation::OP_ANIMATE, $intent->detect('оживи это фото', 1)['operation']);
        $this->assertNull($intent->detect('посоветуй хорошее видео про космос'));
    }

    /** Музыку чат не делает: отвечает карточкой со ссылкой в Студию. */
    public function test_music_request_gets_a_studio_card(): void
    {
        $chat = $this->say(User::factory()->create(), 'Сгенерируй музыку для вечеринки');

        $reply = $this->lastReply($chat);

        $this->assertNull($reply->generation_id);
        $this->assertSame('audio', $reply->studio_link['tab']);
        $this->assertSame('music', $reply->studio_link['operation']);
        $this->assertSame(0, $this->asked);
        $this->assertNull($this->images->lastGeneration);
    }

    public function test_video_card_opens_the_studio_with_the_request(): void
    {
        $user = User::factory()->create();
        $chat = $this->say($user, 'Сделай видео с собакой на пляже');

        $this->actingAs($user)
            ->get("/chats/{$chat->id}")
            ->assertInertia(fn ($page) => $page
                ->where('messages.1.studioLink.tab', 'video')
                ->where('messages.1.studioLink.url', fn ($url) => str_contains($url, 'compose=video')));
    }

    /** Оживить картинку из прошлого ответа: Студия откроется с ней. */
    public function test_animate_card_carries_the_last_picture(): void
    {
        $user = User::factory()->create();

        $chat = $this->say($user, 'Нарисуй собаку');
        $this->say($user, 'Оживи её', $chat);

        $link = $this->lastReply($chat)->studio_link;
        $picture = $chat->messages()->whereNotNull('generation_id')->first()->generation_id;

        $this->assertSame(Generation::OP_ANIMATE, $link['operation']);
        $this->assertSame($picture, $link['work']);
    }

    /** Две приложенные картинки и «объедини» — объединение. */
    public function test_two_photos_are_combined(): void
    {
        $photo = fn (string $name) => UploadedFile::fake()->createWithContent(
            $name,
            base64_decode(FakeImageGenerator::PIXEL),
        );

        $chat = $this->say(
            User::factory()->create(),
            'Объедини эти фото в одно',
            files: [$photo('a.png'), $photo('b.png')],
        );

        $this->assertSame(Generation::OP_COMBINE, $this->lastReply($chat)->generation->operation);
        $this->assertCount(2, $this->images->lastCombine);
    }

    /** Пока ждём ответа, чат знает, что рисуется картинка. */
    public function test_request_kind_is_remembered_on_the_message(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/chats', ['message' => 'Нарисуй собаку']);
        $question = Message::where('role', Message::ROLE_USER)->first();

        $this->assertSame('image', $question->intent);
    }

    public function test_studio_opens_with_a_chat_picture(): void
    {
        $user = User::factory()->create();
        $chat = $this->say($user, 'Нарисуй собаку');
        $work = $this->lastReply($chat)->generation_id;

        $this->actingAs($user)
            ->get("/studio?compose=edit&work={$work}")
            ->assertInertia(fn ($page) => $page
                ->where('compose.tab', 'edit')
                ->where('compose.work.id', $work));
    }

    /** Чужую работу по номеру в Студию не подставить. */
    public function test_studio_ignores_someone_elses_picture(): void
    {
        $chat = $this->say(User::factory()->create(), 'Нарисуй собаку');
        $work = $this->lastReply($chat)->generation_id;

        $this->actingAs(User::factory()->create())
            ->get("/studio?compose=edit&work={$work}")
            ->assertInertia(fn ($page) => $page->where('compose.work', null));
    }
}
