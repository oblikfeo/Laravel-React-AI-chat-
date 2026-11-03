<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\Generation;
use App\Models\Guest;
use App\Models\Message;
use App\Models\UsageRecord;
use App\Models\User;
use App\Services\Ai\AiChatProvider;
use App\Services\Ai\AiResponse;
use App\Services\Ai\SpeechToText;
use App\Services\Chat\Routing\RequestRouter;
use App\Services\Chat\Routing\Route;
use App\Services\Chat\Routing\RoutingInput;
use App\Services\Studio\AudioStudio;
use App\Services\Studio\ImageGenerator;
use App\Services\Usage\UsageLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeAudioStudio;
use Tests\Support\FakeImageGenerator;
use Tests\TestCase;

/**
 * Каждое обращение к провайдеру попадает в журнал со своей ценой,
 * а несостоявшаяся работа из него уходит.
 */
class UsageRecordingTest extends TestCase
{
    use RefreshDatabase;

    /** Что ответит текстовая модель. */
    public AiResponse $answer;

    /** Что решит диспетчер. */
    public ?Route $route = null;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->answer = new AiResponse('A text answer', 'provider/model', 1200, 300, 0.0021);
        $this->app->instance(ImageGenerator::class, new FakeImageGenerator);

        $test = $this;

        $this->app->instance(AiChatProvider::class, new class($test) implements AiChatProvider
        {
            public function __construct(private readonly UsageRecordingTest $test) {}

            public function isConfigured(): bool
            {
                return true;
            }

            public function complete(array $messages, ?string $model = null, array $options = []): AiResponse
            {
                return $this->test->answer;
            }
        });

        $this->app->instance(RequestRouter::class, new class($test) implements RequestRouter
        {
            public function __construct(private readonly UsageRecordingTest $test) {}

            public function route(RoutingInput $input): ?Route
            {
                return $this->test->route;
            }
        });
    }

    private function chat(User $user, string $text): Chat
    {
        $this->actingAs($user)->post('/chats', ['message' => $text]);
        $chat = Chat::latest('id')->first();
        $this->actingAs($user)->post("/chats/{$chat->id}/reply");

        return $chat;
    }

    public function test_chat_reply_is_billed_at_the_price_the_provider_reports(): void
    {
        $user = User::factory()->create();
        $this->route = Route::chat('fast');

        $chat = $this->chat($user, 'Привет');

        $record = UsageRecord::where('category', UsageRecord::CATEGORY_CHAT)->where('model_key', '!=', 'router')->first();
        $reply = $chat->messages()->where('role', Message::ROLE_ASSISTANT)->first();

        $this->assertSame($user->id, $record->user_id);
        $this->assertEqualsWithDelta(0.0021, $record->cost, 1e-9);
        $this->assertSame($reply->id, $record->message_id);
        $this->assertSame(['input_tokens' => 1200, 'output_tokens' => 300], $record->meta);
    }

    /** Провайдер не назвал цену — считаем по токенам и прайсу модели. */
    public function test_chat_reply_without_a_price_is_billed_by_tokens(): void
    {
        $user = User::factory()->create();
        $this->answer = new AiResponse('Answer', 'provider/model', 1_000_000, 1_000_000);
        $this->route = Route::chat();

        $this->chat($user, 'Привет');

        $price = config('models.list.auto.price');
        $record = UsageRecord::where('model_key', 'auto')->first();

        $this->assertEqualsWithDelta($price['input'] + $price['output'], $record->cost, 1e-6);
    }

    public function test_router_call_is_billed_to_the_sender(): void
    {
        $user = User::factory()->create();
        $this->route = new Route('chat', cost: 0.00012);

        $this->chat($user, 'Привет');

        $record = UsageRecord::where('model_key', 'router')->first();

        $this->assertSame($user->id, $record->user_id);
        $this->assertEqualsWithDelta(0.00012, $record->cost, 1e-9);
    }

    public function test_image_is_billed_per_variant(): void
    {
        $user = User::factory()->create(['plan' => 'pro']);

        $this->actingAs($user)->post('/studio', [
            'prompt' => 'Tree',
            'model' => 'standard',
            'aspect_ratio' => '1:1',
            'variants' => 3,
        ]);

        $this->assertSame(1, UsageRecord::count());
        $this->assertEqualsWithDelta(config('studio.models.standard.cost') * 3, UsageRecord::first()->cost, 1e-9);
        $this->assertSame(UsageRecord::CATEGORY_IMAGE, UsageRecord::first()->category);
    }

    public function test_upscale_is_billed_by_its_scale(): void
    {
        $user = User::factory()->create(['plan' => 'pro']);
        $photo = UploadedFile::fake()->createWithContent('a.png', base64_decode(FakeImageGenerator::PIXEL));

        $this->actingAs($user)->post('/studio/edit', [
            'operation' => Generation::OP_UPSCALE,
            'scale' => 4,
            'images' => [$photo],
        ])->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(config('studio.edit.costs.upscale.4'), UsageRecord::first()->cost, 1e-9);
        $this->assertSame(UsageRecord::CATEGORY_EDIT, UsageRecord::first()->category);
    }

    public function test_failed_work_is_refunded(): void
    {
        $this->app->instance(ImageGenerator::class, new FakeImageGenerator(fails: true));

        $user = User::factory()->create();

        $this->actingAs($user)->post('/studio', ['prompt' => 'Tree', 'model' => 'fast', 'aspect_ratio' => '1:1']);

        $this->assertSame(Generation::STATUS_FAILED, Generation::first()->status);
        $this->assertSame(0, UsageRecord::count());
    }

    /** Брошенная работа закрывается по расписанию — и тоже возвращается. */
    public function test_abandoned_work_is_refunded(): void
    {
        $user = User::factory()->create();

        $generation = Generation::create([
            'user_id' => $user->id,
            'kind' => Generation::KIND_IMAGE,
            'operation' => Generation::OP_GENERATE,
            'model_key' => 'fast',
            'status' => Generation::STATUS_PENDING,
            'prompt' => 'Tree',
        ]);
        app(UsageLedger::class)->generation($generation);
        $generation->forceFill(['created_at' => now()->subHour()])->save();

        $this->artisan('studio:collect')->assertSuccessful();

        $this->assertSame(Generation::STATUS_FAILED, $generation->fresh()->status);
        $this->assertSame(0, UsageRecord::count());
    }

    /** Гостевая трата видна сервису, но ничей бюджет не занимает. */
    public function test_guest_work_is_recorded_for_the_guest(): void
    {
        $this->post('/studio', ['prompt' => 'Tree', 'model' => 'fast', 'aspect_ratio' => '1:1']);

        $record = UsageRecord::first();

        $this->assertNull($record->user_id);
        $this->assertSame(Guest::first()->id, $record->guest_id);
    }

    public function test_voice_input_is_billed_by_the_second(): void
    {
        $this->app->instance(SpeechToText::class, new class implements SpeechToText
        {
            public function isAvailable(): bool
            {
                return true;
            }

            public function transcribe(string $audio, string $filename): string
            {
                return 'привет';
            }
        });

        $user = User::factory()->create();

        $this->actingAs($user)->post('/voice/transcribe', [
            'audio' => UploadedFile::fake()->create('voice.webm', 20, 'audio/webm'),
            'seconds' => 12,
        ])->assertOk();

        $record = UsageRecord::first();

        $this->assertSame(UsageRecord::CATEGORY_VOICE, $record->category);
        $this->assertEqualsWithDelta(12 * config('ai.transcription.cost_per_second'), $record->cost, 1e-9);
    }

    /**
     * Смена голоса — по таблице провайдера: до минуты $0.35, до двух
     * $0.69. Длину присылает браузер, но не меньше оценки по размеру.
     */
    public function test_voice_change_is_billed_by_recording_length(): void
    {
        $this->app->instance(AudioStudio::class, new FakeAudioStudio);
        $user = User::factory()->create(['plan' => 'pro']);

        $this->actingAs($user)->post('/studio/voice-change', [
            'recording' => UploadedFile::fake()->createWithContent('a.mp3', str_repeat('x', 1000)),
            'seconds' => 75,
        ])->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(0.69, UsageRecord::first()->cost, 1e-9);

        // Прислали 1 секунду, а файл на три минуты по размеру.
        $this->actingAs($user)->post('/studio/voice-change', [
            'recording' => UploadedFile::fake()->createWithContent('b.mp3', str_repeat('x', 170 * 40_000)),
            'seconds' => 1,
        ]);

        $this->assertEqualsWithDelta(1.04, UsageRecord::latest('id')->first()->cost, 1e-9);
    }

    public function test_background_removal_costs_what_the_provider_charges(): void
    {
        $user = User::factory()->create(['plan' => 'pro']);
        $photo = UploadedFile::fake()->createWithContent('a.png', base64_decode(FakeImageGenerator::PIXEL));

        $this->actingAs($user)->post('/studio/edit', [
            'operation' => Generation::OP_BACKGROUND,
            'images' => [$photo],
        ]);

        $this->assertEqualsWithDelta(0.03, UsageRecord::first()->cost, 1e-9);
    }
}
