<?php

namespace Tests\Feature;

use App\Models\Generation;
use App\Models\User;
use App\Services\Studio\AudioStudio;
use App\Services\Studio\GenerationFailed;
use App\Services\Studio\ImageGenerator;
use App\Services\Studio\Venice\VeniceQueue;
use App\Services\Studio\Venice\VeniceVideoStudio;
use App\Services\Studio\VideoStudio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeAudioStudio;
use Tests\Support\FakeImageGenerator;
use Tests\Support\FakeVideoStudio;
use Tests\TestCase;

/**
 * Видео: ролик по описанию и оживление картинки.
 *
 * Самое дорогое в Студии, поэтому главное здесь — кому можно, сколько
 * раз в день и что уже оплаченная работа не теряется.
 */
class StudioVideoTest extends TestCase
{
    use RefreshDatabase;

    private FakeVideoStudio $studio;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->studio = new FakeVideoStudio;
        $this->app->instance(VideoStudio::class, $this->studio);
        $this->app->instance(ImageGenerator::class, new FakeImageGenerator);
    }

    private function paid(string $plan = 'pro'): User
    {
        return User::factory()->create(['plan' => $plan]);
    }

    private function picture(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'photo.png',
            base64_decode(FakeImageGenerator::PIXEL),
        );
    }

    public function test_video_goes_into_the_queue(): void
    {
        $this->actingAs($this->paid())
            ->post('/studio/video', [
                'prompt' => 'A fox running through snow',
                'duration' => 8,
                'resolution' => '720p',
                'aspect_ratio' => '9:16',
            ])
            ->assertRedirect()
            ->assertSessionMissing('error');

        $generation = Generation::first();

        $this->assertSame(Generation::KIND_VIDEO, $generation->kind);
        $this->assertSame(Generation::OP_VIDEO, $generation->operation);
        $this->assertSame(Generation::STATUS_QUEUED, $generation->status);
        $this->assertNotNull($generation->queue_id);
        $this->assertFalse($generation->is_public);

        $request = $this->studio->lastRequest;

        $this->assertSame(config('studio.video.models.quick.provider_model'), $request->providerModel);
        $this->assertSame(8, $request->duration);
        $this->assertSame('720p', $request->resolution);
        $this->assertSame('9:16', $request->aspectRatio);
        $this->assertNull($request->image);
    }

    /** Провайдер отвергает длину, которой у модели нет. */
    public function test_unknown_duration_is_rejected(): void
    {
        $this->actingAs($this->paid())
            ->post('/studio/video', ['prompt' => 'A fox in snow', 'duration' => 30])
            ->assertSessionHasErrors('duration');

        $this->assertSame(0, $this->studio->queued);
    }

    public function test_video_needs_a_description(): void
    {
        $this->actingAs($this->paid())
            ->post('/studio/video', ['prompt' => ''])
            ->assertSessionHasErrors('prompt');
    }

    public function test_guest_cannot_make_video(): void
    {
        $this->post('/studio/video', ['prompt' => 'A fox in snow'])
            ->assertRedirect('/auth');

        $this->assertSame(0, Generation::count());
    }

    public function test_free_plan_cannot_make_video(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/studio/video', ['prompt' => 'A fox in snow'])
            ->assertSessionHas('error', 'Video is available on paid plans.');

        $this->assertSame(0, Generation::count());
        $this->assertSame(0, $this->studio->queued);
    }

    public function test_daily_limit_stops_video(): void
    {
        $user = $this->paid();
        $limit = config('studio.video.daily_limits.pro');

        for ($i = 0; $i < $limit; $i++) {
            $this->actingAs($user)->post('/studio/video', ['prompt' => 'A fox in snow']);
        }

        $this->actingAs($user)
            ->post('/studio/video', ['prompt' => 'One more fox'])
            ->assertSessionHas('error');

        $this->assertSame($limit, $this->studio->queued);
    }

    /** Несостоявшийся ролик попытку не сжигает. */
    public function test_failed_video_does_not_count_against_the_limit(): void
    {
        $failing = new FakeVideoStudio(fails: true);
        $this->app->instance(VideoStudio::class, $failing);

        $user = $this->paid();
        $limit = config('studio.video.daily_limits.pro');

        for ($i = 0; $i < $limit + 2; $i++) {
            $this->actingAs($user)->post('/studio/video', ['prompt' => 'A fox in snow']);
        }

        $this->assertSame($limit + 2, Generation::where('status', Generation::STATUS_FAILED)->count());

        $this->app->instance(VideoStudio::class, $this->studio);

        $this->actingAs($user)
            ->post('/studio/video', ['prompt' => 'A fox in snow'])
            ->assertSessionMissing('error');

        $this->assertSame(1, $this->studio->queued);
    }

    public function test_higher_plan_gets_more_videos(): void
    {
        $this->actingAs($this->paid('max'))
            ->get('/studio')
            ->assertInertia(fn ($page) => $page
                ->where('video.access', 'open')
                ->where('video.limit.total', config('studio.video.daily_limits.max')));
    }

    public function test_studio_tells_a_free_user_to_upgrade(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/studio')
            ->assertInertia(fn ($page) => $page->where('video.access', 'upgrade'));

        $this->get('/logout');
    }

    public function test_uploaded_image_is_animated(): void
    {
        $this->actingAs($this->paid())
            ->post('/studio/animate', ['image' => $this->picture(), 'duration' => 3])
            ->assertSessionMissing('error')
            ->assertSessionHasNoErrors();

        $generation = Generation::first();

        $this->assertSame(Generation::OP_ANIMATE, $generation->operation);
        $this->assertSame(Generation::STATUS_QUEUED, $generation->status);
        $this->assertNotNull($generation->source_path);
        Storage::disk('local')->assertExists($generation->source_path);

        $request = $this->studio->lastRequest;

        // У оживления своя модель, а пропорции задаёт картинка.
        $this->assertSame(config('studio.video.models.quick.provider_model_image'), $request->providerModel);
        $this->assertNotNull($request->image);
        $this->assertNull($request->aspectRatio);
        $this->assertSame(3, $request->duration);
    }

    public function test_own_studio_work_is_animated(): void
    {
        $user = $this->paid();

        $this->actingAs($user)->post('/studio', ['prompt' => 'A quiet forest', 'model' => 'fast', 'aspect_ratio' => '1:1']);
        $picture = Generation::first();

        $this->actingAs($user)
            ->post('/studio/animate', ['source_id' => $picture->id])
            ->assertSessionMissing('error');

        $this->assertNotNull($this->studio->lastRequest?->image);
    }

    public function test_someone_elses_work_cannot_be_animated(): void
    {
        $this->actingAs($this->paid())->post('/studio', ['prompt' => 'A quiet forest', 'model' => 'fast', 'aspect_ratio' => '1:1']);
        $picture = Generation::first();

        $this->actingAs($this->paid())
            ->post('/studio/animate', ['source_id' => $picture->id])
            ->assertSessionHas('error');

        $this->assertSame(0, $this->studio->queued);
    }

    public function test_animation_needs_an_image(): void
    {
        $this->actingAs($this->paid())
            ->post('/studio/animate', ['prompt' => 'Make it move'])
            ->assertSessionHasErrors('image');
    }

    public function test_ready_video_is_saved(): void
    {
        $user = $this->paid();

        $this->actingAs($user)->post('/studio/video', ['prompt' => 'A fox in snow']);
        $this->studio->ready = true;

        $this->actingAs($user)->post('/studio/collect')->assertSessionHas('success');

        $generation = Generation::first();

        $this->assertSame(Generation::STATUS_READY, $generation->status);
        $this->assertSame('video/mp4', $generation->mime);
        $this->assertStringEndsWith('.mp4', $generation->path);
        Storage::disk('local')->assertExists($generation->path);
    }

    public function test_video_still_in_progress_keeps_waiting(): void
    {
        $user = $this->paid();

        $this->actingAs($user)->post('/studio/video', ['prompt' => 'A fox in snow']);
        $this->actingAs($user)->post('/studio/collect');

        $this->assertSame(Generation::STATUS_QUEUED, Generation::first()->status);
    }

    /** За ролик уже заплачено: минутный сбой провайдера его не губит. */
    public function test_temporary_provider_failure_does_not_lose_the_video(): void
    {
        $user = $this->paid();

        $this->actingAs($user)->post('/studio/video', ['prompt' => 'A fox in snow']);

        $this->studio->retrieveFailure = GenerationFailed::temporary('Сбой сервера.');
        $this->actingAs($user)->post('/studio/collect');

        $this->assertSame(Generation::STATUS_QUEUED, Generation::first()->status);

        $this->studio->retrieveFailure = null;
        $this->studio->ready = true;
        $this->actingAs($user)->post('/studio/collect');

        $this->assertSame(Generation::STATUS_READY, Generation::first()->status);
    }

    /** Обрыв связи — не ошибка 500 и не потерянный ролик. */
    public function test_network_failure_is_survived(): void
    {
        config(['studio.base_url' => 'https://provider.test/api/v1']);

        $queue = new VeniceQueue('https://provider.test/api/v1', 'key', 5);
        $this->app->instance(VideoStudio::class, new VeniceVideoStudio($queue));

        Http::fake(fn () => throw new ConnectionException('timeout'));

        $user = $this->paid();

        // Поставить в работу не вышло: работа помечена несостоявшейся.
        $this->actingAs($user)
            ->post('/studio/video', ['prompt' => 'A fox in snow'])
            ->assertRedirect();

        $this->assertSame(Generation::STATUS_FAILED, Generation::first()->status);

        // А уже считающаяся работа связь пережидает.
        Generation::first()->forceFill([
            'status' => Generation::STATUS_QUEUED,
            'queue_id' => 'job-1',
        ])->save();

        $this->actingAs($user)->post('/studio/collect')->assertRedirect();

        $this->assertSame(Generation::STATUS_QUEUED, Generation::first()->status);
    }

    public function test_final_provider_refusal_marks_the_video_failed(): void
    {
        $user = $this->paid();

        $this->actingAs($user)->post('/studio/video', ['prompt' => 'A fox in snow']);

        $this->studio->retrieveFailure = new GenerationFailed('Отказ.');
        $this->actingAs($user)->post('/studio/collect');

        $this->assertSame(Generation::STATUS_FAILED, Generation::first()->status);
    }

    public function test_forgotten_video_does_not_hang_forever(): void
    {
        $user = $this->paid();

        $this->actingAs($user)->post('/studio/video', ['prompt' => 'A fox in snow']);

        $this->travel(config('studio.video.timeout_seconds') + 60)->seconds();
        $this->actingAs($user)->post('/studio/collect');

        $this->assertSame(Generation::STATUS_FAILED, Generation::first()->status);
    }

    /** Ролик доходит до галереи, даже если страницу закрыли. */
    public function test_background_command_collects_the_video(): void
    {
        $this->actingAs($this->paid())->post('/studio/video', ['prompt' => 'A fox in snow']);
        $this->studio->ready = true;

        $this->artisan('studio:collect')->assertSuccessful();

        $this->assertSame(Generation::STATUS_READY, Generation::first()->status);
    }

    public function test_animation_is_asked_about_with_its_own_model(): void
    {
        $user = $this->paid();

        $this->actingAs($user)->post('/studio/animate', ['image' => $this->picture()]);
        $this->actingAs($user)->post('/studio/collect');

        $this->assertSame(
            config('studio.video.models.quick.provider_model_image'),
            $this->studio->lastRetrieveModel,
        );
    }

    public function test_download_address_is_remembered(): void
    {
        $this->studio->downloadUrl = 'https://files.example/clip.mp4';
        $user = $this->paid();

        $this->actingAs($user)->post('/studio/video', ['prompt' => 'A fox in snow']);
        $this->actingAs($user)->post('/studio/collect');

        $this->assertSame('https://files.example/clip.mp4', $this->studio->lastDownloadUrl);
    }

    public function test_video_can_be_made_again(): void
    {
        $user = $this->paid();

        $this->actingAs($user)->post('/studio/video', ['prompt' => 'A fox in snow', 'duration' => 3]);

        $this->actingAs($user)
            ->post('/studio/'.Generation::first()->id.'/retry')
            ->assertSessionMissing('error');

        $this->assertSame(2, $this->studio->queued);
        $this->assertSame(3, Generation::latest('id')->first()->duration);
    }

    public function test_animation_can_be_made_again_from_the_kept_image(): void
    {
        $user = $this->paid();

        $this->actingAs($user)->post('/studio/animate', ['image' => $this->picture()]);

        $this->actingAs($user)
            ->post('/studio/'.Generation::first()->id.'/retry')
            ->assertSessionMissing('error');

        $this->assertSame(2, $this->studio->queued);
        $this->assertNotNull($this->studio->lastRequest->image);
    }

    public function test_video_never_reaches_the_feed(): void
    {
        $user = $this->paid();

        $this->actingAs($user)->post('/studio/video', ['prompt' => 'A fox in snow']);
        $this->studio->ready = true;
        $this->actingAs($user)->post('/studio/collect');

        $this->actingAs($this->paid())
            ->get('/feed')
            ->assertInertia(fn ($page) => $page->where('works.data', []));
    }

    /** Проигрыватель запрашивает ролик кусками: иначе нет перемотки. */
    public function test_video_file_supports_partial_requests(): void
    {
        $user = $this->paid();

        $this->actingAs($user)->post('/studio/video', ['prompt' => 'A fox in snow']);
        $this->studio->ready = true;
        $this->actingAs($user)->post('/studio/collect');

        $this->actingAs($user)
            ->get('/studio/'.Generation::first()->id.'/file', ['Range' => 'bytes=0-3'])
            ->assertStatus(206)
            ->assertHeader('Content-Type', 'video/mp4');
    }

    public function test_someone_elses_video_stays_private(): void
    {
        $user = $this->paid();

        $this->actingAs($user)->post('/studio/video', ['prompt' => 'A fox in snow']);
        $this->studio->ready = true;
        $this->actingAs($user)->post('/studio/collect');

        $this->actingAs($this->paid())
            ->get('/studio/'.Generation::first()->id.'/file')
            ->assertForbidden();
    }

    /** Музыка стоит денег за секунду: длину держим в границах модели. */
    public function test_music_cannot_be_longer_than_the_model_allows(): void
    {
        $this->app->instance(AudioStudio::class, new FakeAudioStudio);

        $this->actingAs($this->paid())
            ->post('/studio/music', [
                'prompt' => 'Calm piano for a rainy evening',
                'model' => 'instrumental',
                'duration' => 600,
            ])
            ->assertSessionHasErrors('duration');
    }

    public function test_studio_shows_what_each_tool_costs(): void
    {
        $this->actingAs($this->paid())
            ->get('/studio')
            ->assertInertia(fn ($page) => $page
                ->where('models.0.price.flat', 0.01)
                ->where('videoModels.0.prices.text.480p.5', 0.12)
                ->where('editPrices.edit', 0.04)
                ->has('musicModels.0.price.perSecond'));
    }
}
