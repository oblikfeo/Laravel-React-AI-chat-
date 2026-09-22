<?php

namespace Tests\Feature;

use App\Actions\Chat\SendMessage;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Services\Ai\AiChatProvider;
use App\Services\Ai\AiResponse;
use App\Services\Ai\OpenAiCompatibleProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_provider_is_used_once_key_is_set(): void
    {
        config([
            'ai.provider' => 'openrouter',
            'ai.providers.openrouter.api_key' => 'test-key',
        ]);

        $this->app->forgetInstance(AiChatProvider::class);

        $this->assertInstanceOf(
            OpenAiCompatibleProvider::class,
            $this->app->make(AiChatProvider::class)
        );
    }

    public function test_provider_sends_history_and_stores_reply(): void
    {
        Http::fake([
            '*/chat/completions' => Http::response([
                'model' => 'test-model',
                'choices' => [
                    ['message' => ['content' => 'Ответ модели']],
                ],
            ]),
        ]);

        $provider = new OpenAiCompatibleProvider(
            'https://example.test/v1', 'key', 'test-model', 10
        );

        $chat = Chat::factory()->for(User::factory())->create();

        (new SendMessage($provider))->handle($chat, 'Вопрос');

        $this->assertSame(2, $chat->messages()->count());
        $this->assertSame(
            'Ответ модели',
            $chat->messages()->where('role', Message::ROLE_ASSISTANT)->first()->content
        );

        // Модели уходит системный промпт вместе с историей диалога.
        Http::assertSent(function ($request) {
            return $request['messages'][0]['role'] === 'system'
                && $request['messages'][1]['content'] === 'Вопрос';
        });
    }

    public function test_provider_failure_does_not_break_the_chat(): void
    {
        Http::fake(['*/chat/completions' => Http::response('boom', 500)]);

        $provider = new OpenAiCompatibleProvider(
            'https://example.test/v1', 'key', 'test-model', 10
        );

        $chat = Chat::factory()->for(User::factory())->create();

        (new SendMessage($provider))->handle($chat, 'Вопрос');

        // Вопрос сохранён, а вместо ответа — понятное пояснение.
        $this->assertSame(2, $chat->messages()->count());
        $this->assertStringContainsString(
            'Could not get a response',
            $chat->messages()->latest('id')->first()->content
        );
    }
}
