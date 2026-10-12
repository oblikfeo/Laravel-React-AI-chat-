<?php

namespace Tests\Feature;

use App\Models\Generation;
use App\Models\User;
use App\Services\Studio\GenerationRequest;
use App\Services\Studio\ImageGenerator;
use App\Services\Studio\Venice\VeniceClient;
use App\Services\Studio\Venice\VeniceGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Support\FakeImageGenerator;
use Tests\TestCase;

/**
 * Что бы ни сломалось, человек не видит страницу ошибки.
 *
 * 12.10 у сервера на секунды пропал DNS, запрос к провайдеру не ушёл,
 * и вместо несостоявшейся картинки открылось окно с ошибкой 500 —
 * прямо на показе заказчику. Эти тесты про то, чтобы так больше не
 * было: сбой превращается в работу с пометкой «не получилось».
 */
class StudioResilienceTest extends TestCase
{
    use RefreshDatabase;

    private const INPUT = ['prompt' => 'A quiet forest', 'model' => 'fast', 'aspect_ratio' => '1:1'];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    /** Тот самый случай: провайдер недоступен по сети. */
    public function test_lost_connection_does_not_break_the_page(): void
    {
        $this->app->instance(ImageGenerator::class, new VeniceGenerator(
            new VeniceClient('https://provider.test/api/v1', 'key', 5),
        ));

        Http::fake(fn () => throw new ConnectionException('Resolving timed out'));

        $this->actingAs(User::factory()->create())
            ->post('/studio', self::INPUT)
            ->assertRedirect();

        $generation = Generation::first();

        $this->assertSame(Generation::STATUS_FAILED, $generation->status);
        $this->assertNotNull($generation->failure_reason);
    }

    /** Непредвиденная ошибка внутри генерации — тоже не страница 500. */
    public function test_unexpected_error_marks_the_work_failed(): void
    {
        $this->app->instance(ImageGenerator::class, new class extends FakeImageGenerator
        {
            public function generate(GenerationRequest $request): array
            {
                throw new RuntimeException('Что-то пошло не так.');
            }
        });

        $this->actingAs(User::factory()->create())
            ->post('/studio', self::INPUT)
            ->assertRedirect();

        // Работа не осталась «в процессе» навсегда.
        $this->assertSame(Generation::STATUS_FAILED, Generation::first()->status);
    }

    /**
     * Последняя страховка: ошибка там, где её никто не ловит, оставляет
     * человека на странице с сообщением.
     */
    public function test_any_failed_action_keeps_the_person_on_the_page(): void
    {
        $this->mock(ImageGenerator::class)
            ->shouldReceive('isAvailable')
            ->andThrow(new RuntimeException('Сбой вне генерации.'));

        $this->actingAs(User::factory()->create())
            ->from('/studio')
            ->post('/studio', self::INPUT, ['X-Inertia' => 'true'])
            ->assertRedirect('/studio')
            ->assertSessionHas('error');
    }

    /** Ошибки проверки формы страховка не трогает. */
    public function test_validation_errors_still_reach_the_form(): void
    {
        $this->app->instance(ImageGenerator::class, new FakeImageGenerator());

        $this->actingAs(User::factory()->create())
            ->post('/studio', ['prompt' => ''], ['X-Inertia' => 'true'])
            ->assertSessionHasErrors('prompt')
            ->assertSessionMissing('error');
    }

    /** Работа, запрос которой оборвался, не крутится вечно. */
    public function test_abandoned_work_is_closed(): void
    {
        $user = User::factory()->create();

        $old = Generation::create([
            'user_id' => $user->id,
            'kind' => Generation::KIND_IMAGE,
            'operation' => Generation::OP_GENERATE,
            'model_key' => 'fast',
            'status' => Generation::STATUS_PENDING,
            'prompt' => 'Stuck',
            'aspect_ratio' => '1:1',
        ]);
        $old->forceFill(['created_at' => now()->subMinutes(20)])->save();

        $recent = Generation::create([
            'user_id' => $user->id,
            'kind' => Generation::KIND_IMAGE,
            'operation' => Generation::OP_GENERATE,
            'model_key' => 'fast',
            'status' => Generation::STATUS_PENDING,
            'prompt' => 'Still going',
            'aspect_ratio' => '1:1',
        ]);

        $this->artisan('studio:collect')->assertSuccessful();

        $this->assertSame(Generation::STATUS_FAILED, $old->fresh()->status);
        $this->assertSame(Generation::STATUS_PENDING, $recent->fresh()->status);
    }
}
