<?php

namespace Tests\Feature;

use App\Models\Generation;
use App\Models\User;
use App\Services\Studio\AudioStudio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeAudioStudio;
use Tests\TestCase;

/**
 * Музыка, эффекты и смена голоса.
 *
 * Все три считаются у провайдера дольше, чем длится запрос страницы,
 * поэтому работают через очередь: задача ставится, результат
 * забирается отдельно.
 */
class StudioAudioTest extends TestCase
{
    use RefreshDatabase;

    private FakeAudioStudio $studio;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->studio = new FakeAudioStudio();
        $this->app->instance(AudioStudio::class, $this->studio);
    }

    public function test_music_goes_into_the_queue(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/studio/music', [
                'prompt' => 'Calm piano for a rainy evening',
                'model' => 'instrumental',
            ])
            ->assertRedirect();

        $generation = Generation::first();

        $this->assertNotNull($generation);
        $this->assertSame(Generation::STATUS_QUEUED, $generation->status);
        $this->assertSame(Generation::OP_MUSIC, $generation->operation);
        $this->assertNotNull($generation->queue_id);
        $this->assertNotNull($this->studio->lastMusic);
    }

    /** Короткое описание модель не примет. */
    public function test_music_needs_a_real_description(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/studio/music', ['prompt' => 'jazz'])
            ->assertSessionHasErrors('prompt');
    }

    /** Модель, которая поёт, без слов работать не станет. */
    public function test_singing_model_requires_lyrics(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/studio/music', [
                'prompt' => 'A cheerful song about the sea',
                'model' => 'vocal',
            ])
            ->assertSessionHasErrors('lyrics');
    }

    public function test_effect_goes_into_the_queue(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/studio/effect', [
                'prompt' => 'Thunder rolling in the distance',
                'model' => 'quick',
                'duration' => 5,
            ])
            ->assertRedirect();

        $generation = Generation::first();

        $this->assertSame(Generation::OP_EFFECT, $generation->operation);
        $this->assertSame(Generation::STATUS_QUEUED, $generation->status);
        $this->assertSame(5, $this->studio->lastEffect->duration);
    }

    public function test_voice_change_takes_a_recording(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/studio/voice-change', [
                'recording' => UploadedFile::fake()->createWithContent(
                    'speech.mp3',
                    'fake-recording',
                ),
                'voice' => 'deep',
            ])
            ->assertRedirect();

        $generation = Generation::first();

        $this->assertSame(Generation::OP_VOICE_CHANGE, $generation->operation);
        $this->assertSame('fake-recording', $this->studio->lastRecording);
        // Наружу уходит название голоса у провайдера, а не наш ключ.
        $this->assertSame('am_onyx', $this->studio->lastVoice);
    }

    public function test_voice_change_without_a_recording_is_refused(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/studio/voice-change', ['voice' => 'warm'])
            ->assertSessionHasErrors('recording');
    }

    /** Пока провайдер считает, работа остаётся в ожидании. */
    public function test_unfinished_work_stays_queued(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/studio/music', [
            'prompt' => 'Slow ambient drone for deep focus',
        ]);

        $this->actingAs($user)->post('/studio/collect');

        $this->assertSame(
            Generation::STATUS_QUEUED,
            Generation::first()->status,
        );
    }

    public function test_finished_work_is_saved(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/studio/music', [
            'prompt' => 'Slow ambient drone for deep focus',
        ]);

        $this->studio->ready = true;

        $this->actingAs($user)->post('/studio/collect')->assertRedirect();

        $generation = Generation::first();

        $this->assertSame(Generation::STATUS_READY, $generation->status);
        $this->assertNotNull($generation->path);
        Storage::disk('local')->assertExists($generation->path);
    }

    /**
     * Смена голоса забирается своим эндпоинтом: общий для звука её
     * не знает.
     */
    public function test_voice_change_is_collected_from_its_own_endpoint(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/studio/voice-change', [
            'recording' => UploadedFile::fake()->createWithContent('a.mp3', 'x'),
        ]);

        $this->actingAs($user)->post('/studio/collect');

        $this->assertTrue($this->studio->lastVoiceChangeFlag);
    }

    /** Задача, о которой провайдер забыл, не должна висеть вечно. */
    public function test_a_forgotten_job_gives_up(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/studio/music', [
            'prompt' => 'Slow ambient drone for deep focus',
        ]);

        Generation::first()->forceFill([
            'created_at' => now()->subSeconds(
                (int) config('studio.queue.timeout_seconds') + 60,
            ),
        ])->save();

        $this->actingAs($user)->post('/studio/collect');

        $this->assertSame(
            Generation::STATUS_FAILED,
            Generation::first()->status,
        );
    }

    /** Чужие работы при проверке готовности не трогаем. */
    public function test_another_users_work_is_not_collected(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->post('/studio/music', [
            'prompt' => 'Slow ambient drone for deep focus',
        ]);

        $this->studio->ready = true;

        $this->actingAs(User::factory()->create())->post('/studio/collect');

        $this->assertSame(
            Generation::STATUS_QUEUED,
            Generation::first()->status,
        );
    }

    /** Имя модели у провайдера наружу не отдаётся. */
    public function test_provider_names_are_not_exposed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/studio/music', [
            'prompt' => 'Calm piano for a rainy evening',
        ]);

        $this->actingAs($user)
            ->get('/studio')
            ->assertOk()
            ->assertDontSee('sonilo')
            ->assertDontSee('ace-step')
            ->assertDontSee('af_heart');
    }

    /**
     * Провайдер ищет задачу по номеру вместе с моделью: по одному
     * номеру он отвечает отказом.
     */
    public function test_model_is_sent_when_collecting(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/studio/effect', [
            'prompt' => 'Thunder rolling in the distance',
            'model' => 'quick',
        ]);

        $this->actingAs($user)->post('/studio/collect');

        $this->assertSame(
            config('studio.effects.models.quick.provider_model'),
            $this->studio->lastRetrieveModel,
        );
    }

    /**
     * Повтор звука должен звать звуковые инструменты.
     *
     * Раньше он слепо звал рисование и падал на ключе модели, которой
     * среди картинок нет.
     */
    public function test_audio_can_be_repeated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/studio/effect', [
            'prompt' => 'Thunder rolling in the distance',
            'model' => 'quick',
        ]);

        $first = Generation::first();

        $this->actingAs($user)
            ->post("/studio/{$first->id}/retry")
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Generation::where('operation', Generation::OP_EFFECT)->count());
    }

    /**
     * Смену голоса повторить нечем: исходной записи у нас нет.
     */
    public function test_voice_change_cannot_be_repeated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/studio/voice-change', [
            'recording' => UploadedFile::fake()->createWithContent('a.mp3', 'x'),
        ]);

        $generation = Generation::first();

        $this->actingAs($user)
            ->post("/studio/{$generation->id}/retry")
            ->assertSessionHas('error');

        $this->assertSame(1, Generation::count());
    }
}
