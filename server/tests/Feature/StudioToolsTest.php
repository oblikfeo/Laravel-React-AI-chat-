<?php

namespace Tests\Feature;

use App\Models\Generation;
use App\Models\User;
use App\Services\Studio\ImageGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeImageGenerator;
use Tests\TestCase;

/**
 * Инструменты Студии: правка, объединение, увеличение, удаление фона
 * и озвучка.
 */
class StudioToolsTest extends TestCase
{
    use RefreshDatabase;

    private FakeImageGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->generator = new FakeImageGenerator();
        $this->app->instance(ImageGenerator::class, $this->generator);
    }

    /**
     * Картинка на диске пользователя — исходник для правки.
     *
     * Файл готовый, а не нарисованный на лету: расширение GD стоит не
     * на каждой машине, а тесты должны идти везде.
     */
    private function upload(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'source.png',
            base64_decode(FakeImageGenerator::PIXEL),
        );
    }

    /**
     * Готовая работа в галерее.
     *
     * Скрытая: в этих тестах проверяется доступ к чужому, а работу
     * из общей ленты брать как раз можно.
     */
    private function existingImage(User $user): Generation
    {
        $this->actingAs($user)->post('/studio', [
            'prompt' => 'A cube',
            'model' => 'fast',
            'aspect_ratio' => '1:1',
            'is_public' => false,
        ]);

        return Generation::first();
    }

    public function test_image_is_edited_from_an_upload(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/studio/edit', [
                'operation' => 'edit',
                'prompt' => 'Make it blue',
                'images' => [$this->upload()],
            ])
            ->assertRedirect();

        $generation = Generation::where('operation', 'edit')->first();

        $this->assertNotNull($generation);
        $this->assertSame(Generation::STATUS_READY, $generation->status);
        $this->assertSame('Make it blue', $this->generator->lastEdit->prompt);
        Storage::disk('local')->assertExists($generation->path);
    }

    /**
     * Править можно и свою готовую работу, не загружая её заново.
     */
    public function test_own_generation_can_be_used_as_a_source(): void
    {
        $user = User::factory()->create();
        $source = $this->existingImage($user);

        $this->actingAs($user)
            ->post('/studio/edit', [
                'operation' => 'edit',
                'prompt' => 'Add a shadow',
                'source_ids' => [$source->id],
            ])
            ->assertRedirect();

        $edited = Generation::where('operation', 'edit')->first();

        $this->assertNotNull($edited);
        $this->assertSame($source->id, $edited->source_generation_id);
    }

    /**
     * Чужая работа исходником быть не может: иначе по номеру можно
     * было бы вытащить чужую картинку.
     */
    public function test_another_users_generation_is_not_accepted(): void
    {
        $source = $this->existingImage(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->post('/studio/edit', [
                'operation' => 'edit',
                'prompt' => 'Steal this',
                'source_ids' => [$source->id],
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, Generation::where('operation', 'edit')->count());
    }

    public function test_images_are_combined(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/studio/edit', [
                'operation' => 'combine',
                'prompt' => 'Put them side by side',
                'images' => [$this->upload(), $this->upload()],
            ])
            ->assertRedirect();

        $this->assertCount(2, $this->generator->lastCombine);
        $this->assertSame(
            Generation::STATUS_READY,
            Generation::where('operation', 'combine')->first()->status,
        );
    }

    public function test_image_is_upscaled(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/studio/edit', [
                'operation' => 'upscale',
                'images' => [$this->upload()],
                'scale' => 4,
            ])
            ->assertRedirect();

        $this->assertSame(4, $this->generator->lastScale);
        $this->assertSame(
            Generation::STATUS_READY,
            Generation::where('operation', 'upscale')->first()->status,
        );
    }

    public function test_background_is_removed(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/studio/edit', [
                'operation' => 'background_remove',
                'images' => [$this->upload()],
            ])
            ->assertRedirect();

        $this->assertTrue($this->generator->backgroundRemoved);
    }

    /**
     * Увеличение и удаление фона описания не требуют: человек ничего
     * не пишет, работа всё равно должна называться.
     */
    public function test_tools_without_a_prompt_get_a_title(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/studio/edit', [
                'operation' => 'upscale',
                'images' => [$this->upload()],
            ]);

        $this->assertNotEmpty(
            Generation::where('operation', 'upscale')->first()->prompt,
        );
    }

    public function test_editing_requires_a_prompt(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/studio/edit', [
                'operation' => 'edit',
                'images' => [$this->upload()],
            ])
            ->assertSessionHasErrors('prompt');
    }

    public function test_editing_without_an_image_is_refused(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/studio/edit', [
                'operation' => 'edit',
                'prompt' => 'Change something',
            ])
            ->assertSessionHas('error');
    }

    public function test_speech_is_created(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/studio/speech', [
                'text' => 'Hello from Uncensia',
                'model' => 'standard',
            ])
            ->assertRedirect();

        $generation = Generation::where('kind', Generation::KIND_AUDIO)->first();

        $this->assertNotNull($generation);
        $this->assertSame(Generation::STATUS_READY, $generation->status);
        $this->assertSame('Hello from Uncensia', $this->generator->lastSpeech->text);
        Storage::disk('local')->assertExists($generation->path);
    }

    public function test_speech_requires_text(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/studio/speech', ['model' => 'standard'])
            ->assertSessionHasErrors('text');
    }

    /** Несколько вариантов одной идеи становятся отдельными работами. */
    public function test_variants_create_separate_works(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/studio', [
                'prompt' => 'Four cats',
                'model' => 'fast',
                'aspect_ratio' => '1:1',
                'variants' => 3,
            ])
            ->assertRedirect();

        $this->assertSame(3, Generation::count());
        $this->assertSame(3, $this->generator->lastGeneration->variants);
    }

    /** Имя модели у провайдера наружу не отдаётся. */
    public function test_provider_model_is_not_exposed_for_audio(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/studio/speech', [
            'text' => 'Test',
            'model' => 'standard',
        ]);

        $this->actingAs($user)
            ->get('/studio')
            ->assertOk()
            ->assertDontSee('tts-kokoro');
    }

    /**
     * Соотношение сторон понимает не всякая модель: остальные
     * отвечают отказом на лишний параметр и ждут размеры.
     */
    public function test_models_without_aspect_ratio_get_sizes(): void
    {
        $this->actingAs(User::factory()->create())->post('/studio', [
            'prompt' => 'A tall tower',
            'model' => 'fast',
            'aspect_ratio' => '9:16',
        ]);

        $request = $this->generator->lastGeneration;

        $this->assertFalse($request->supportsAspectRatio);
        $this->assertSame(720, $request->width);
        $this->assertSame(1280, $request->height);
    }

    /** Модель, которая умеет соотношение, получает именно его. */
    public function test_model_with_aspect_ratio_keeps_it(): void
    {
        $this->actingAs(User::factory()->create())->post('/studio', [
            'prompt' => 'A wide landscape',
            'model' => 'pro',
            'aspect_ratio' => '16:9',
        ]);

        $request = $this->generator->lastGeneration;

        $this->assertTrue($request->supportsAspectRatio);
        $this->assertSame('16:9', $request->aspectRatio);
    }

    /**
     * Зерно не должно превышать предел провайдера: он отвечает
     * отказом, и генерация срывается на ровном месте.
     */
    public function test_generated_seed_fits_the_provider_limit(): void
    {
        $max = (int) config('studio.max_seed');

        for ($attempt = 0; $attempt < 20; $attempt++) {
            Generation::query()->delete();

            $this->actingAs(User::factory()->create())->post('/studio', [
                'prompt' => 'A seed check',
                'model' => 'fast',
                'aspect_ratio' => '1:1',
            ]);

            $seed = Generation::first()->seed;

            $this->assertGreaterThan(0, $seed);
            $this->assertLessThanOrEqual($max, $seed);
        }
    }

    public function test_seed_above_the_limit_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/studio', [
                'prompt' => 'Too big',
                'model' => 'fast',
                'aspect_ratio' => '1:1',
                'seed' => (int) config('studio.max_seed') + 1,
            ])
            ->assertSessionHasErrors('seed');
    }

    /**
     * Неудачная попытка нужна недолго: человек должен понять, что
     * запрос был. Старые висят в ленте пустыми квадратами.
     */
    public function test_old_failures_leave_the_feed(): void
    {
        $user = User::factory()->create();

        $fresh = Generation::create([
            'user_id' => $user->id,
            'model_key' => 'fast',
            'status' => Generation::STATUS_FAILED,
            'prompt' => 'Только что',
            'aspect_ratio' => '1:1',
        ]);

        $old = Generation::create([
            'user_id' => $user->id,
            'model_key' => 'fast',
            'status' => Generation::STATUS_FAILED,
            'prompt' => 'Давно',
            'aspect_ratio' => '1:1',
        ]);

        $old->forceFill(['created_at' => now()->subHours(2)])->save();

        $this->actingAs($user)
            ->get('/studio')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where(
                'generations',
                fn ($items) => collect($items)->pluck('id')->all() === [$fresh->id],
            ));
    }

    /**
     * Объединять можно и свои готовые работы, не загружая их заново.
     */
    public function test_several_own_works_can_be_combined(): void
    {
        $user = User::factory()->create();

        $first = $this->existingImage($user);

        $this->actingAs($user)->post('/studio', [
            'prompt' => 'A second one',
            'model' => 'fast',
            'aspect_ratio' => '1:1',
        ]);

        $second = Generation::where('operation', Generation::OP_GENERATE)
            ->latest('id')
            ->first();

        $this->actingAs($user)
            ->post('/studio/edit', [
                'operation' => 'combine',
                'prompt' => 'Side by side',
                'source_ids' => [$first->id, $second->id],
            ])
            ->assertRedirect();

        $this->assertCount(2, $this->generator->lastCombine);
        $this->assertSame(
            Generation::STATUS_READY,
            Generation::where('operation', 'combine')->first()->status,
        );
    }

    /** Чужие работы в список исходников не попадают. */
    public function test_foreign_works_are_skipped_in_a_list(): void
    {
        $user = User::factory()->create();
        $mine = $this->existingImage($user);
        $foreign = $this->existingImage(User::factory()->create());

        $this->actingAs($user)
            ->post('/studio/edit', [
                'operation' => 'combine',
                'prompt' => 'Try to mix',
                'source_ids' => [$mine->id, $foreign->id],
            ]);

        // Осталась одна своя картинка, а двух для объединения мало.
        $this->assertNull($this->generator->lastCombine);
    }

    /**
     * Повтор работы с моделью, которой больше нет в линейке, не
     * должен ронять запрос: работы живут дольше настроек.
     */
    public function test_missing_model_does_not_break_a_retry(): void
    {
        $user = User::factory()->create();
        $generation = $this->existingImage($user);

        $generation->forceFill(['model_key' => 'gone'])->save();

        $this->actingAs($user)
            ->post("/studio/{$generation->id}/retry")
            ->assertRedirect();

        $this->assertSame(
            Generation::STATUS_FAILED,
            Generation::latest('id')->first()->status,
        );
    }
}
