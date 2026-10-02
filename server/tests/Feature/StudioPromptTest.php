<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Ai\AiChatProvider;
use App\Services\Ai\AiResponse;
use App\Services\Studio\ImageGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeImageGenerator;
use Tests\TestCase;

/**
 * Описание уходит модели рисования на английском: на нём они обучены,
 * а русский разбирают плохо и рисуют не то, что просили.
 */
class StudioPromptTest extends TestCase
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

    /** Переводчик, который всегда отвечает одной и той же фразой. */
    private function fakeTranslator(string $answer = 'a naked dog'): void
    {
        $this->app->bind(AiChatProvider::class, fn () => new class($answer) implements AiChatProvider
        {
            public function __construct(private readonly string $answer)
            {
            }

            public function isConfigured(): bool
            {
                return true;
            }

            public function complete(array $messages, ?string $model = null): AiResponse
            {
                return new AiResponse(content: $this->answer);
            }
        });
    }

    public function test_russian_prompt_is_translated(): void
    {
        $this->fakeTranslator('a naked dog');

        $this->actingAs(User::factory()->create())->post('/studio', [
            'prompt' => 'хочу увидеть голую собаку',
            'model' => 'fast',
            'aspect_ratio' => '1:1',
        ]);

        $this->assertSame('a naked dog', $this->generator->lastGeneration->prompt);
    }

    /** Английское описание переводить незачем. */
    public function test_english_prompt_is_left_alone(): void
    {
        $this->fakeTranslator('SHOULD NOT BE USED');

        $this->actingAs(User::factory()->create())->post('/studio', [
            'prompt' => 'a red apple on a table',
            'model' => 'fast',
            'aspect_ratio' => '1:1',
        ]);

        $this->assertSame(
            'a red apple on a table',
            $this->generator->lastGeneration->prompt,
        );
    }

    /**
     * В ленте человек должен видеть то, что написал сам, а не
     * машинный перевод.
     */
    public function test_original_prompt_is_kept_for_the_feed(): void
    {
        $this->fakeTranslator('a naked dog');

        $this->actingAs(User::factory()->create())->post('/studio', [
            'prompt' => 'хочу увидеть голую собаку',
            'model' => 'fast',
            'aspect_ratio' => '1:1',
        ]);

        $this->assertSame(
            'хочу увидеть голую собаку',
            \App\Models\Generation::first()->prompt,
        );
    }

    /**
     * Если текстовая модель недоступна, рисование всё равно должно
     * состояться: лучше неточная картинка, чем ошибка.
     */
    public function test_generation_survives_a_broken_translator(): void
    {
        $this->app->bind(AiChatProvider::class, fn () => new class implements AiChatProvider
        {
            public function isConfigured(): bool
            {
                return true;
            }

            public function complete(array $messages, ?string $model = null): AiResponse
            {
                throw new \RuntimeException('провайдер недоступен');
            }
        });

        $this->actingAs(User::factory()->create())
            ->post('/studio', [
                'prompt' => 'хочу увидеть голую собаку',
                'model' => 'fast',
                'aspect_ratio' => '1:1',
            ])
            ->assertRedirect();

        $this->assertSame(
            \App\Models\Generation::STATUS_READY,
            \App\Models\Generation::first()->status,
        );
    }
}
