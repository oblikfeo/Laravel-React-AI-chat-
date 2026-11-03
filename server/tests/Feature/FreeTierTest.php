<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\Generation;
use App\Models\Guest;
use App\Models\Message;
use App\Models\User;
use App\Services\Studio\AudioStudio;
use App\Services\Studio\ImageGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeAudioStudio;
use Tests\Support\FakeImageGenerator;
use Tests\TestCase;

/**
 * Без подписки: дневные пределы по разделам и закрытые дорогие
 * настройки. Иначе один длинный звук съедал бы треть бюджета.
 */
class FreeTierTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->app->instance(ImageGenerator::class, new FakeImageGenerator);
        $this->app->instance(AudioStudio::class, new FakeAudioStudio);
    }

    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('a.png', base64_decode(FakeImageGenerator::PIXEL));
    }

    public function test_free_plan_gets_three_images_a_day(): void
    {
        $user = User::factory()->create();
        $payload = ['prompt' => 'Tree', 'model' => 'fast', 'aspect_ratio' => '1:1'];

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($user)->post('/studio', $payload)->assertSessionMissing('error');
        }

        $this->actingAs($user)
            ->post('/studio', $payload)
            ->assertSessionHas('error', fn ($message) => str_contains($message, "today's free images"));

        $this->assertSame(3, Generation::count());
    }

    public function test_free_plan_gets_two_edits_a_day(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 2; $i++) {
            $this->actingAs($user)->post('/studio/edit', [
                'operation' => Generation::OP_BACKGROUND,
                'images' => [$this->photo()],
            ])->assertSessionMissing('error');
        }

        $this->actingAs($user)
            ->post('/studio/edit', ['operation' => Generation::OP_BACKGROUND, 'images' => [$this->photo()]])
            ->assertSessionHas('error');
    }

    /** Тот самый случай: трёхминутный звук без подписки не запускается. */
    public function test_long_sound_effect_is_closed_without_a_subscription(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/studio/effect', ['prompt' => 'Spacious boom for a trailer', 'model' => 'long', 'duration' => 180])
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'paid plans'));

        $this->actingAs($user)
            ->post('/studio/effect', ['prompt' => 'Spacious boom for a trailer', 'model' => 'quick', 'duration' => 30])
            ->assertSessionHas('error', fn ($message) => str_contains($message, '10 seconds'));

        $this->assertSame(0, Generation::count());
    }

    public function test_free_plan_gets_one_short_sound_a_day(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/studio/effect', ['prompt' => 'Door knock', 'model' => 'quick', 'duration' => 5])
            ->assertSessionMissing('error');

        $this->actingAs($user)
            ->post('/studio/music', ['prompt' => 'Calm piano', 'model' => 'instrumental', 'duration' => 30])
            ->assertSessionHas('error');

        $this->assertSame(1, Generation::count());
    }

    public function test_music_is_capped_at_thirty_seconds(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/studio/music', ['prompt' => 'Calm piano', 'model' => 'instrumental', 'duration' => 120])
            ->assertSessionHas('error', fn ($message) => str_contains($message, '30 seconds'));
    }

    public function test_voice_changer_and_big_upscale_need_a_subscription(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/studio/voice-change', ['recording' => UploadedFile::fake()->create('a.mp3', 10, 'audio/mpeg')])
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'paid plans'));

        $this->actingAs($user)
            ->post('/studio/edit', ['operation' => Generation::OP_UPSCALE, 'scale' => 4, 'images' => [$this->photo()]])
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'paid plans'));

        $this->actingAs($user)
            ->post('/studio', ['prompt' => 'Tree', 'model' => 'fast', 'aspect_ratio' => '1:1', 'variants' => 4])
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'one at a time'));
    }

    /** Подписчика пределы не касаются: только бюджет. */
    public function test_subscriber_is_not_capped(): void
    {
        $user = User::factory()->create(['plan' => 'pro']);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)
                ->post('/studio', ['prompt' => 'Tree', 'model' => 'fast', 'aspect_ratio' => '1:1'])
                ->assertSessionMissing('error');
        }

        $this->actingAs($user)
            ->post('/studio/effect', ['prompt' => 'Spacious boom', 'model' => 'long', 'duration' => 60])
            ->assertSessionMissing('error');
    }

    /** Форма сразу показывает закрытое: модели под замком, длина урезана. */
    public function test_studio_shows_what_is_closed(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/studio')
            ->assertInertia(fn ($page) => $page
                ->where('maxVariants', 1)
                ->where('upscaleScales', [2])
                ->where('voiceChange', false)
                ->where('maxSpeech', 1000)
                ->where('effectModels.1.key', 'long')
                ->where('effectModels.1.locked', true)
                ->where('effectModels.0.maxDuration', 10)
                ->where('musicModels.0.maxDuration', 30)
                ->where('limits.image.percentLeft', 100));

        $this->actingAs(User::factory()->create(['plan' => 'pro']))
            ->get('/studio')
            ->assertInertia(fn ($page) => $page
                ->where('maxVariants', config('studio.max_variants'))
                ->where('voiceChange', true)
                ->where('effectModels.1.locked', false));
    }

    public function test_free_plan_gets_fifty_messages_a_day(): void
    {
        $user = User::factory()->create();
        $chat = Chat::create(['user_id' => $user->id, 'title' => 'Test', 'model_key' => 'auto', 'last_message_at' => now()]);

        for ($i = 0; $i < 50; $i++) {
            $chat->messages()->create(['role' => Message::ROLE_USER, 'content' => "Message {$i}"]);
        }

        $this->actingAs($user)
            ->post("/chats/{$chat->id}/messages", ['message' => 'One more'])
            ->assertSessionHas('error', fn ($message) => str_contains($message, "today's free messages"));
    }

    public function test_guest_gets_two_images_and_no_audio(): void
    {
        $payload = ['prompt' => 'Tree', 'model' => 'fast', 'aspect_ratio' => '1:1'];

        $this->post('/studio', $payload);
        $cookie = [config('guests.cookie_name') => Guest::first()->token];

        $this->withCookies($cookie)->post('/studio', $payload)->assertSessionMissing('error');
        $this->withCookies($cookie)->post('/studio', $payload)->assertSessionHas('error');

        $this->withCookies($cookie)
            ->post('/studio/effect', ['prompt' => 'Door knock', 'model' => 'quick', 'duration' => 5])
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'Sign up'));

        $this->assertSame(2, Generation::count());
    }
}
