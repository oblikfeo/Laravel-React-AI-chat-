<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Services\Ai\AiChatProvider;
use App\Services\Ai\AiResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeImageGenerator;
use Tests\TestCase;

/**
 * Персонажи: создание, каталог, диалог.
 *
 * Главное здесь — доступ: личный персонаж не должен быть виден
 * посторонним, а инструкции автора не должны утекать никому.
 */
class CharactersTest extends TestCase
{
    use RefreshDatabase;

    /** Что получил провайдер в последнем запросе. */
    private array $sentMessages = [];

    private array $sentOptions = [];

    private ?string $sentModel = null;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        // Провайдер, который запоминает запрос: так проверяется, что
        // до модели дошла именно роль персонажа.
        $this->app->instance(AiChatProvider::class, new class($this) implements AiChatProvider
        {
            public function __construct(private readonly CharactersTest $test)
            {
            }

            public function isConfigured(): bool
            {
                return true;
            }

            public function complete(array $messages, ?string $model = null, array $options = []): AiResponse
            {
                $this->test->remember($messages, $model, $options);

                return new AiResponse(content: 'In character reply', model: 'test-model');
            }
        });
    }

    public function remember(array $messages, ?string $model, array $options): void
    {
        $this->sentMessages = $messages;
        $this->sentModel = $model;
        $this->sentOptions = $options;
    }

    /** Поля формы по умолчанию. */
    private function form(array $overrides = []): array
    {
        return [
            'name' => 'Captain Mira',
            'description' => 'A starship captain with a dry sense of humour',
            'tags' => ['Sci-Fi', 'Adventure'],
            'intro' => 'Welcome aboard. What brings you to my ship?',
            'instructions' => 'You are a calm, witty starship captain.',
            'model' => 'roleplay',
            'is_public' => true,
            ...$overrides,
        ];
    }

    private function makeCharacter(User $author, array $overrides = []): Character
    {
        $this->actingAs($author)->post('/characters', $this->form($overrides));

        return Character::latest('id')->first();
    }

    // ── Создание ──────────────────────────────────────────────

    public function test_character_is_created(): void
    {
        $author = User::factory()->create();

        $this->actingAs($author)
            ->post('/characters', $this->form())
            ->assertRedirect('/characters?tab=mine');

        $character = Character::first();

        $this->assertNotNull($character);
        $this->assertSame($author->id, $character->user_id);
        $this->assertSame('Captain Mira', $character->name);
        $this->assertSame(['Sci-Fi', 'Adventure'], $character->tags);
        $this->assertTrue($character->is_public);
    }

    /** У персонажа должен быть автор: гость создавать не может. */
    public function test_guest_cannot_create(): void
    {
        $this->post('/characters', $this->form())->assertRedirect();

        $this->assertSame(0, Character::count());
    }

    public function test_name_and_instructions_are_required(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/characters', $this->form(['name' => '', 'instructions' => '']))
            ->assertSessionHasErrors(['name', 'instructions']);
    }

    /** По умолчанию персонаж личный: публикация — осознанный шаг. */
    public function test_character_is_private_unless_asked(): void
    {
        $character = $this->makeCharacter(
            User::factory()->create(),
            ['is_public' => false],
        );

        $this->assertFalse($character->is_public);
        $this->assertFalse($character->isListed());
    }

    public function test_avatar_is_stored(): void
    {
        $character = $this->makeCharacter(User::factory()->create(), [
            'avatar' => UploadedFile::fake()->createWithContent(
                'face.png',
                base64_decode(FakeImageGenerator::PIXEL),
            ),
        ]);

        $this->assertNotNull($character->avatar_path);
        Storage::disk('local')->assertExists($character->avatar_path);
    }

    /** Из документа берётся текст: модель файлов не открывает. */
    public function test_context_file_becomes_text(): void
    {
        $character = $this->makeCharacter(User::factory()->create(), [
            'context' => UploadedFile::fake()->createWithContent(
                'lore.txt',
                'The ship is called Aurora.',
            ),
        ]);

        $this->assertSame('lore.txt', $character->context_name);
        $this->assertSame('The ship is called Aurora.', $character->context_text);
    }

    public function test_tags_are_cleaned(): void
    {
        $character = $this->makeCharacter(User::factory()->create(), [
            'tags' => ['Fantasy', 'fantasy', '  Magic  ', ''],
        ]);

        $this->assertSame(['Fantasy', 'Magic'], $character->tags);
    }

    // ── Правка и удаление ─────────────────────────────────────

    public function test_author_can_edit(): void
    {
        $author = User::factory()->create();
        $character = $this->makeCharacter($author);

        $this->actingAs($author)
            ->post("/characters/{$character->id}", $this->form(['name' => 'Admiral Mira']))
            ->assertRedirect();

        $this->assertSame('Admiral Mira', $character->fresh()->name);
    }

    public function test_stranger_cannot_edit(): void
    {
        $character = $this->makeCharacter(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->post("/characters/{$character->id}", $this->form(['name' => 'Hijacked']))
            ->assertForbidden();

        $this->assertSame('Captain Mira', $character->fresh()->name);
    }

    public function test_stranger_cannot_delete(): void
    {
        $character = $this->makeCharacter(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->delete("/characters/{$character->id}")
            ->assertForbidden();

        $this->assertSame(1, Character::count());
    }

    /**
     * Переписка остаётся у того, кто её вёл, даже если автор удалил
     * персонажа.
     */
    public function test_deleting_keeps_peoples_chats(): void
    {
        $author = User::factory()->create();
        $character = $this->makeCharacter($author);

        $reader = User::factory()->create();
        $this->actingAs($reader)->post("/characters/{$character->id}/chat");

        $this->actingAs($author)->delete("/characters/{$character->id}")->assertRedirect();

        $this->assertSame(0, Character::count());

        $chat = Chat::where('user_id', $reader->id)->first();

        $this->assertNotNull($chat);
        $this->assertNull($chat->character_id);
    }

    // ── Каталог ───────────────────────────────────────────────

    public function test_catalog_opens_for_a_guest(): void
    {
        $this->get('/characters')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Characters/Index'));
    }

    public function test_public_character_is_in_the_catalog(): void
    {
        $character = $this->makeCharacter(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->get('/characters')
            ->assertInertia(fn ($page) => $page->where(
                'catalog',
                fn ($items) => collect($items)->pluck('id')->contains($character->id),
            ));
    }

    public function test_private_character_is_not_in_the_catalog(): void
    {
        $character = $this->makeCharacter(User::factory()->create(), ['is_public' => false]);

        $this->actingAs(User::factory()->create())
            ->get('/characters')
            ->assertInertia(fn ($page) => $page->where(
                'catalog',
                fn ($items) => ! collect($items)->pluck('id')->contains($character->id),
            ));
    }

    /**
     * Персонаж на безцензурной модели остаётся личным, даже если
     * автор включил публикацию.
     */
    public function test_uncensored_character_stays_private(): void
    {
        $character = $this->makeCharacter(User::factory()->create(), [
            'model' => 'uncensored',
            'is_public' => true,
        ]);

        $this->assertFalse($character->is_public);

        $this->actingAs(User::factory()->create())
            ->get('/characters')
            ->assertInertia(fn ($page) => $page->where(
                'catalog',
                fn ($items) => ! collect($items)->pluck('id')->contains($character->id),
            ));
    }

    /** Инструкции, память и документ посторонним не отдаются. */
    public function test_catalog_does_not_leak_the_authors_work(): void
    {
        $this->makeCharacter(User::factory()->create(), [
            'instructions' => 'SECRET-PERSONALITY-TEXT',
            'system_prompt' => 'SECRET-SYSTEM-PROMPT',
            'memories' => ['SECRET-MEMORY'],
            'context' => UploadedFile::fake()->createWithContent('lore.txt', 'SECRET-LORE'),
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/characters')
            ->assertOk()
            ->assertDontSee('SECRET-PERSONALITY-TEXT')
            ->assertDontSee('SECRET-SYSTEM-PROMPT')
            ->assertDontSee('SECRET-MEMORY')
            ->assertDontSee('SECRET-LORE');
    }

    /** Автор видит своего персонажа целиком — чтобы править. */
    public function test_author_gets_the_full_character(): void
    {
        $author = User::factory()->create();
        $this->makeCharacter($author, ['instructions' => 'MY-OWN-INSTRUCTIONS']);

        $this->actingAs($author)
            ->get('/characters?tab=mine')
            ->assertInertia(fn ($page) => $page
                ->where('tab', 'mine')
                ->where('mine.0.instructions', 'MY-OWN-INSTRUCTIONS'));
    }

    /** Название модели у провайдера наружу не выходит. */
    public function test_provider_model_is_not_exposed(): void
    {
        $author = User::factory()->create();
        $this->makeCharacter($author);

        $this->actingAs($author)
            ->get('/characters?tab=mine')
            ->assertOk()
            ->assertDontSee('euryale');
    }

    public function test_catalog_is_searchable(): void
    {
        $author = User::factory()->create();
        $mira = $this->makeCharacter($author);
        $other = $this->makeCharacter($author, ['name' => 'Old Wizard', 'description' => 'Knows spells']);

        $this->get('/characters?q=wizard')
            ->assertInertia(fn ($page) => $page->where(
                'catalog',
                fn ($items) => collect($items)->pluck('id')->all() === [$other->id],
            ));

        $this->assertNotSame($mira->id, $other->id);
    }

    public function test_catalog_filters_by_tag(): void
    {
        $author = User::factory()->create();
        $this->makeCharacter($author, ['tags' => ['Sci-Fi']]);
        $fantasy = $this->makeCharacter($author, ['name' => 'Elf', 'tags' => ['Fantasy']]);

        $this->get('/characters?tag=Fantasy')
            ->assertInertia(fn ($page) => $page->where(
                'catalog',
                fn ($items) => collect($items)->pluck('id')->all() === [$fantasy->id],
            ));
    }

    // ── Диалог ────────────────────────────────────────────────

    public function test_chat_starts_with_the_intro(): void
    {
        $character = $this->makeCharacter(User::factory()->create());
        $reader = User::factory()->create();

        $this->actingAs($reader)
            ->post("/characters/{$character->id}/chat")
            ->assertRedirect();

        $chat = Chat::where('user_id', $reader->id)->first();

        $this->assertSame($character->id, $chat->character_id);
        $this->assertSame('Captain Mira', $chat->title);

        $first = $chat->messages()->first();

        $this->assertSame(Message::ROLE_ASSISTANT, $first->role);
        $this->assertSame('Welcome aboard. What brings you to my ship?', $first->content);
    }

    /**
     * Диалог с персонажем один и постоянный: повторный заход
     * продолжает прежнюю переписку.
     */
    public function test_reopening_continues_the_same_chat(): void
    {
        $character = $this->makeCharacter(User::factory()->create());
        $reader = User::factory()->create();

        $this->actingAs($reader)->post("/characters/{$character->id}/chat");
        $this->actingAs($reader)->post("/characters/{$character->id}/chat");
        $this->actingAs($reader)->post("/characters/{$character->id}/chat");

        $this->assertSame(1, Chat::where('user_id', $reader->id)->count());
        // Вступление не повторяется при каждом заходе.
        $this->assertSame(1, Message::count());
    }

    /** У каждого человека своя переписка с одним и тем же персонажем. */
    public function test_each_person_has_their_own_chat(): void
    {
        $character = $this->makeCharacter(User::factory()->create());

        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->actingAs($first)->post("/characters/{$character->id}/chat");
        $this->actingAs($second)->post("/characters/{$character->id}/chat");

        $this->assertSame(2, Chat::where('character_id', $character->id)->count());
    }

    public function test_private_character_cannot_be_opened_by_others(): void
    {
        $character = $this->makeCharacter(User::factory()->create(), ['is_public' => false]);

        $this->actingAs(User::factory()->create())
            ->post("/characters/{$character->id}/chat")
            ->assertNotFound();

        $this->assertSame(0, Chat::count());
    }

    public function test_author_can_talk_to_a_private_character(): void
    {
        $author = User::factory()->create();
        $character = $this->makeCharacter($author, ['is_public' => false]);

        $this->actingAs($author)
            ->post("/characters/{$character->id}/chat")
            ->assertRedirect();

        $this->assertSame(1, Chat::where('character_id', $character->id)->count());
    }

    public function test_guest_can_talk_to_a_public_character(): void
    {
        $character = $this->makeCharacter(User::factory()->create());

        auth()->logout();

        $this->post("/characters/{$character->id}/chat")->assertRedirect();

        $chat = Chat::where('character_id', $character->id)->first();

        $this->assertNotNull($chat);
        $this->assertNull($chat->user_id);
        $this->assertNotNull($chat->guest_id);
        // Гостю платные модели закрыты.
        $this->assertSame(config('guests.model'), $chat->model_key);
    }

    /** До модели доходит роль персонажа, а не наш обычный помощник. */
    public function test_model_receives_the_character(): void
    {
        $author = User::factory()->create();
        $character = $this->makeCharacter($author, [
            'memories' => ['The user is called Sam'],
            'temperature' => 1.2,
            'context' => UploadedFile::fake()->createWithContent('lore.txt', 'The ship is called Aurora.'),
        ]);

        $this->actingAs($author)->post("/characters/{$character->id}/chat");
        $chat = Chat::first();

        $this->actingAs($author)->post("/chats/{$chat->id}/messages", ['message' => 'Hello']);
        $this->actingAs($author)->post("/chats/{$chat->id}/reply");

        $system = $this->sentMessages[0];

        $this->assertSame('system', $system['role']);
        $this->assertStringContainsString('Captain Mira', $system['content']);
        $this->assertStringContainsString('calm, witty starship captain', $system['content']);
        $this->assertStringContainsString('The user is called Sam', $system['content']);
        $this->assertStringContainsString('The ship is called Aurora.', $system['content']);
        $this->assertStringNotContainsString('You are Uncensia', $system['content']);

        // Модель и температура — те, что выбрал автор.
        $this->assertSame(config('models.list.roleplay.provider_model'), $this->sentModel);
        $this->assertSame(1.2, $this->sentOptions['temperature']);

        // Вступление персонажа — часть переписки.
        $this->assertSame('assistant', $this->sentMessages[1]['role']);
    }

    /** Свой системный запрос автора уходит как есть, без обвязки. */
    public function test_custom_system_prompt_replaces_ours(): void
    {
        $author = User::factory()->create();
        $character = $this->makeCharacter($author, [
            'system_prompt' => 'CUSTOM PROMPT ONLY',
            'intro' => null,
        ]);

        $this->actingAs($author)->post("/characters/{$character->id}/chat");
        $chat = Chat::first();

        $this->actingAs($author)->post("/chats/{$chat->id}/messages", ['message' => 'Hi']);
        $this->actingAs($author)->post("/chats/{$chat->id}/reply");

        $this->assertStringStartsWith('CUSTOM PROMPT ONLY', $this->sentMessages[0]['content']);
        $this->assertStringNotContainsString('Stay in character', $this->sentMessages[0]['content']);
    }

    /** Обычный чат остаётся обычным. */
    public function test_plain_chat_is_untouched(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/chats', ['message' => 'Hello', 'model' => 'auto']);
        $chat = Chat::first();

        $this->actingAs($user)->post("/chats/{$chat->id}/reply");

        $this->assertSame(config('ai.system_prompt'), $this->sentMessages[0]['content']);
        $this->assertSame([], $this->sentOptions);
    }

    public function test_chat_page_shows_the_character(): void
    {
        $author = User::factory()->create();
        $character = $this->makeCharacter($author);

        $this->actingAs($author)->post("/characters/{$character->id}/chat");
        $chat = Chat::first();

        $this->actingAs($author)
            ->get("/chats/{$chat->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('character.id', $character->id)
                ->where('character.name', 'Captain Mira'));
    }

    // ── Аватар ────────────────────────────────────────────────

    public function test_avatar_of_a_private_character_is_hidden(): void
    {
        $character = $this->makeCharacter(User::factory()->create(), [
            'is_public' => false,
            'avatar' => UploadedFile::fake()->createWithContent(
                'face.png',
                base64_decode(FakeImageGenerator::PIXEL),
            ),
        ]);

        $this->actingAs(User::factory()->create())
            ->get("/characters/{$character->id}/avatar")
            ->assertNotFound();
    }

    public function test_avatar_of_a_public_character_is_served(): void
    {
        $character = $this->makeCharacter(User::factory()->create(), [
            'avatar' => UploadedFile::fake()->createWithContent(
                'face.png',
                base64_decode(FakeImageGenerator::PIXEL),
            ),
        ]);

        $this->actingAs(User::factory()->create())
            ->get("/characters/{$character->id}/avatar")
            ->assertOk();
    }

    // ── Дописывание текста ────────────────────────────────────

    public function test_description_can_be_written_for_the_author(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/characters/write', [
                'target' => 'description',
                'name' => 'Captain Mira',
            ])
            ->assertOk()
            ->assertJson(['text' => 'In character reply']);
    }

    public function test_writing_needs_something_to_build_on(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/characters/write', ['target' => 'description'])
            ->assertStatus(422);
    }

    // ── Лента ─────────────────────────────────────────────────

    public function test_feed_has_a_characters_tab(): void
    {
        $author = User::factory()->create();
        $public = $this->makeCharacter($author);
        $private = $this->makeCharacter($author, ['name' => 'Hidden', 'is_public' => false]);

        $this->actingAs(User::factory()->create())
            ->get('/feed?tab=characters')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('tab', 'characters')
                ->where(
                    'characters',
                    fn ($items) => collect($items)->pluck('id')->all() === [$public->id],
                ));

        $this->assertFalse($private->isListed());
    }
}
