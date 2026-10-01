<?php

namespace Tests\Feature;

use App\Models\Generation;
use App\Models\User;
use App\Services\Studio\ImageGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeImageGenerator;
use Tests\TestCase;

class StudioTest extends TestCase
{
    use RefreshDatabase;

    private ?FakeImageGenerator $generator = null;

    /** Подставляет провайдера, который рисует, не выходя в сеть. */
    private function fakeGenerator(bool $available = true): FakeImageGenerator
    {
        $this->generator = new FakeImageGenerator($available);

        $this->app->instance(ImageGenerator::class, $this->generator);

        return $this->generator;
    }

    public function test_studio_page_opens(): void
    {
        $this->fakeGenerator();

        $this->actingAs(User::factory()->create())
            ->get('/studio')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Studio/Index')
                ->where('studioReady', true)
            );
    }

    public function test_image_is_created_and_stored(): void
    {
        Storage::fake('local');
        $this->fakeGenerator();

        $user = User::factory()->create();

        $this->actingAs($user)->post('/studio', [
            'prompt' => 'A red cube',
            'model' => 'fast',
            'aspect_ratio' => '1:1',
        ])->assertRedirect();

        $generation = Generation::first();

        $this->assertNotNull($generation);
        $this->assertSame(Generation::STATUS_READY, $generation->status);
        $this->assertSame($user->id, $generation->user_id);
        Storage::disk('local')->assertExists($generation->path);
    }

    /**
     * Зерно запоминается всегда: без него повторная генерация дала бы
     * совсем другую картинку.
     */
    public function test_seed_is_always_recorded(): void
    {
        Storage::fake('local');
        $this->fakeGenerator();

        $this->actingAs(User::factory()->create())->post('/studio', [
            'prompt' => 'A blue sphere',
            'model' => 'fast',
            'aspect_ratio' => '1:1',
        ]);

        $this->assertNotNull(Generation::first()->seed);
    }

    public function test_guest_can_create_an_image(): void
    {
        Storage::fake('local');
        $this->fakeGenerator();

        $this->post('/studio', [
            'prompt' => 'A guest drawing',
            'model' => 'fast',
            'aspect_ratio' => '1:1',
        ])->assertRedirect();

        $generation = Generation::first();

        $this->assertNull($generation->user_id);
        $this->assertNotNull($generation->guest_id);
    }

    public function test_guest_limit_stops_after_a_few(): void
    {
        Storage::fake('local');
        $this->fakeGenerator();
        config(['studio.daily_limit_guest' => 1]);

        $payload = ['prompt' => 'One', 'model' => 'fast', 'aspect_ratio' => '1:1'];

        $this->post('/studio', $payload);

        // Гость узнаётся по куке: без неё каждый запрос был бы новым
        // посетителем, и лимит не накапливался бы.
        $token = \App\Models\Guest::first()->token;

        $this->withCookie(config('guests.cookie_name'), $token)
            ->post('/studio', $payload)
            ->assertSessionHas('error');

        $this->assertSame(1, Generation::count());
    }

    /**
     * Пока провайдер не открыл доступ, интерфейс должен сообщать об
     * этом обычными словами, а не падать с ошибкой.
     */
    public function test_generation_is_refused_while_unavailable(): void
    {
        $this->fakeGenerator(available: false);

        $this->actingAs(User::factory()->create())
            ->post('/studio', [
                'prompt' => 'Anything',
                'model' => 'fast',
                'aspect_ratio' => '1:1',
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, Generation::count());
    }

    public function test_prompt_is_required(): void
    {
        $this->fakeGenerator();

        $this->actingAs(User::factory()->create())
            ->post('/studio', ['model' => 'fast', 'aspect_ratio' => '1:1'])
            ->assertSessionHasErrors('prompt');
    }

    public function test_unknown_model_is_rejected(): void
    {
        $this->fakeGenerator();

        $this->actingAs(User::factory()->create())
            ->post('/studio', [
                'prompt' => 'Test',
                'model' => 'nonsense',
                'aspect_ratio' => '1:1',
            ])
            ->assertSessionHasErrors('model');
    }

    public function test_another_users_image_is_not_served(): void
    {
        Storage::fake('local');
        $this->fakeGenerator();

        $owner = User::factory()->create();
        $this->actingAs($owner)->post('/studio', [
            'prompt' => 'Private',
            'model' => 'fast',
            'aspect_ratio' => '1:1',
        ]);

        $generation = Generation::first();

        $this->actingAs(User::factory()->create())
            ->get("/studio/{$generation->id}/file")
            ->assertForbidden();
    }

    public function test_deleting_removes_the_file(): void
    {
        Storage::fake('local');
        $this->fakeGenerator();

        $user = User::factory()->create();
        $this->actingAs($user)->post('/studio', [
            'prompt' => 'To delete',
            'model' => 'fast',
            'aspect_ratio' => '1:1',
        ]);

        $generation = Generation::first();
        $path = $generation->path;

        $this->actingAs($user)->delete("/studio/{$generation->id}");

        $this->assertSame(0, Generation::count());
        Storage::disk('local')->assertMissing($path);
    }

    /**
     * Название модели у провайдера — внутренняя деталь, наружу
     * отдаём только наш ярлык.
     */
    public function test_provider_model_is_not_exposed(): void
    {
        Storage::fake('local');
        $this->fakeGenerator();

        $user = User::factory()->create();
        $this->actingAs($user)->post('/studio', [
            'prompt' => 'Test',
            'model' => 'fast',
            'aspect_ratio' => '1:1',
        ]);

        $this->actingAs($user)
            ->get('/studio')
            ->assertInertia(fn ($page) => $page
                ->where('generations.0.model', 'Quick')
            );

        $this->actingAs($user)
            ->get('/studio')
            ->assertDontSee('gemini', false);
    }
}
