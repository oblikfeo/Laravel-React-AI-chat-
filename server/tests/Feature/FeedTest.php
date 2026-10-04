<?php

namespace Tests\Feature;

use App\Models\Generation;
use App\Models\User;
use App\Services\Studio\ImageGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeImageGenerator;
use Tests\TestCase;

/**
 * Общая лента.
 *
 * Показываем только то, что люди согласились показать: чужая скрытая
 * работа не должна попасть ни в ленту, ни по прямой ссылке.
 */
class FeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->app->instance(ImageGenerator::class, new FakeImageGenerator());
    }

    /** Создаёт работу указанного человека. */
    private function makeWork(User $user, array $input = []): Generation
    {
        $this->actingAs($user)->post('/studio', [
            'prompt' => 'A quiet forest',
            'model' => 'fast',
            'aspect_ratio' => '1:1',
            ...$input,
        ]);

        return Generation::latest('id')->first();
    }

    public function test_feed_opens(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/feed')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Feed/Index'));
    }

    public function test_public_work_is_shown(): void
    {
        $work = $this->makeWork(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->get('/feed')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where(
                'works.data',
                fn ($items) => collect($items)->pluck('id')->contains($work->id),
            ));
    }

    /** По умолчанию работа видна: так решено при создании. */
    public function test_work_is_public_by_default(): void
    {
        $this->assertTrue($this->makeWork(User::factory()->create())->is_public);
    }

    public function test_hidden_work_stays_out_of_the_feed(): void
    {
        $work = $this->makeWork(User::factory()->create(), ['is_public' => false]);

        $this->assertFalse($work->is_public);

        $this->actingAs(User::factory()->create())
            ->get('/feed')
            ->assertInertia(fn ($page) => $page->where(
                'works.data',
                fn ($items) => ! collect($items)->pluck('id')->contains($work->id),
            ));
    }

    /**
     * Безцензурные модели в ленту не идут: их работы человек создаёт
     * для себя, а не для чужих глаз.
     */
    public function test_uncensored_work_never_reaches_the_feed(): void
    {
        $work = $this->makeWork(User::factory()->create(), [
            'model' => 'uncensored',
            'is_public' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/feed')
            ->assertInertia(fn ($page) => $page->where(
                'works.data',
                fn ($items) => ! collect($items)->pluck('id')->contains($work->id),
            ));
    }

    /** Записи в ленту не попадают: она про картинки. */
    public function test_audio_is_not_in_the_feed(): void
    {
        $user = User::factory()->create();

        Generation::create([
            'user_id' => $user->id,
            'kind' => Generation::KIND_AUDIO,
            'operation' => Generation::OP_MUSIC,
            'model_key' => 'instrumental',
            'status' => Generation::STATUS_READY,
            'is_public' => true,
            'prompt' => 'Calm piano',
            'path' => 'studio/u1/track.mp3',
            'disk' => 'local',
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/feed')
            ->assertInertia(fn ($page) => $page->where(
                'works.data',
                fn ($items) => count($items) === 0,
            ));
    }

    /** Чужую работу из ленты можно открыть: она на то и в ленте. */
    public function test_public_file_opens_for_everyone(): void
    {
        $work = $this->makeWork(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->get("/studio/{$work->id}/file")
            ->assertOk();
    }

    /** Скрытая чужая работа закрыта и по прямой ссылке. */
    public function test_hidden_file_stays_private(): void
    {
        $work = $this->makeWork(User::factory()->create(), ['is_public' => false]);

        $this->actingAs(User::factory()->create())
            ->get("/studio/{$work->id}/file")
            ->assertForbidden();
    }

    /**
     * Работа безцензурной модели не открывается чужому, даже если
     * помечена открытой: в ленте её нет.
     */
    public function test_uncensored_file_stays_private(): void
    {
        $work = $this->makeWork(User::factory()->create(), [
            'model' => 'uncensored',
            'is_public' => true,
        ]);

        $this->actingAs(User::factory()->create())
            ->get("/studio/{$work->id}/file")
            ->assertForbidden();
    }

    /** Свою скрытую работу владелец открывает как обычно. */
    public function test_owner_still_sees_their_hidden_work(): void
    {
        $owner = User::factory()->create();
        $work = $this->makeWork($owner, ['is_public' => false]);

        $this->actingAs($owner)
            ->get("/studio/{$work->id}/file")
            ->assertOk();
    }

    /** Работу из ленты можно взять за основу для правки. */
    public function test_feed_work_can_be_edited(): void
    {
        $work = $this->makeWork(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->post('/studio/edit', [
                'operation' => 'edit',
                'prompt' => 'Make it brighter',
                'source_ids' => [$work->id],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(
            Generation::STATUS_READY,
            Generation::where('operation', 'edit')->first()->status,
        );
    }

    /** Скрытую чужую работу за основу взять нельзя. */
    public function test_hidden_work_cannot_be_edited_by_others(): void
    {
        $work = $this->makeWork(User::factory()->create(), ['is_public' => false]);

        $this->actingAs(User::factory()->create())
            ->post('/studio/edit', [
                'operation' => 'edit',
                'prompt' => 'Steal this',
                'source_ids' => [$work->id],
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, Generation::where('operation', 'edit')->count());
    }

    /** Чужие настройки и владелец в ленту не отдаются. */
    public function test_feed_does_not_expose_private_details(): void
    {
        $this->makeWork(User::factory()->create(), ['seed' => 12345]);

        $this->actingAs(User::factory()->create())
            ->get('/feed')
            ->assertOk()
            ->assertDontSee('12345')
            ->assertDontSee('z-image-turbo');
    }

    /** Работу из ленты можно обсудить: заводится диалог с ней. */
    public function test_feed_work_opens_a_chat(): void
    {
        $work = $this->makeWork(User::factory()->create());
        $reader = User::factory()->create();

        $this->actingAs($reader)
            ->post('/chats/from-feed', ['generation_id' => $work->id])
            ->assertRedirect();

        $chat = \App\Models\Chat::first();

        $this->assertNotNull($chat);
        $this->assertSame($reader->id, $chat->user_id);

        $attachment = \App\Models\Attachment::first();

        $this->assertNotNull($attachment);
        Storage::disk('local')->assertExists($attachment->path);

        // Файл именно скопирован: удаление чужой работы не должно
        // оставить диалог без картинки.
        $this->assertNotSame($work->path, $attachment->path);
    }

    /** Скрытую работу в чат не утащить. */
    public function test_hidden_work_cannot_start_a_chat(): void
    {
        $work = $this->makeWork(User::factory()->create(), ['is_public' => false]);

        $this->actingAs(User::factory()->create())
            ->post('/chats/from-feed', ['generation_id' => $work->id])
            ->assertNotFound();
    }

    /** Студия открывается с работой из ленты. */
    public function test_studio_opens_with_a_feed_work(): void
    {
        $work = $this->makeWork(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->get("/studio?from_feed={$work->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('fromFeed.id', $work->id));
    }

    /** Скрытая работа в Студию по ссылке не подставляется. */
    public function test_studio_ignores_a_hidden_work(): void
    {
        $work = $this->makeWork(User::factory()->create(), ['is_public' => false]);

        $this->actingAs(User::factory()->create())
            ->get("/studio?from_feed={$work->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('fromFeed', null));
    }
}
