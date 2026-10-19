<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Ai\SpeechToText;
use App\Services\Studio\GenerationFailed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Голосовой ввод: запись с микрофона превращается в текст.
 */
class VoiceInputTest extends TestCase
{
    use RefreshDatabase;

    private function speech(?string $text, bool $available = true): void
    {
        $this->app->instance(SpeechToText::class, new class($text, $available) implements SpeechToText
        {
            public function __construct(private readonly ?string $text, private readonly bool $available) {}

            public function isAvailable(): bool
            {
                return $this->available;
            }

            public function transcribe(string $audio, string $filename): string
            {
                if ($this->text === null) {
                    throw new GenerationFailed('Сбой.');
                }

                return $this->text;
            }
        });
    }

    private function recording(): UploadedFile
    {
        return UploadedFile::fake()->create('voice.webm', 20, 'audio/webm');
    }

    public function test_recording_becomes_text(): void
    {
        $this->speech('Нарисуй собаку на пляже');

        $this->actingAs(User::factory()->create())
            ->post('/voice/transcribe', ['audio' => $this->recording()])
            ->assertOk()
            ->assertJson(['text' => 'Нарисуй собаку на пляже']);
    }

    /** Гость тоже может диктовать: чат открыт и ему. */
    public function test_guest_can_dictate(): void
    {
        $this->speech('Привет');

        $this->post('/voice/transcribe', ['audio' => $this->recording()])
            ->assertOk()
            ->assertJson(['text' => 'Привет']);
    }

    public function test_failed_recognition_is_explained(): void
    {
        $this->speech(null);

        $this->post('/voice/transcribe', ['audio' => $this->recording()])
            ->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    public function test_silence_is_explained(): void
    {
        $this->speech('');

        $this->post('/voice/transcribe', ['audio' => $this->recording()])
            ->assertStatus(422);
    }

    public function test_only_audio_is_accepted(): void
    {
        $this->speech('Текст');

        $this->postJson('/voice/transcribe', [
            'audio' => UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf'),
        ])->assertStatus(422)->assertJsonValidationErrors('audio');
    }

    public function test_voice_input_without_provider_says_so(): void
    {
        $this->speech('Текст', available: false);

        $this->post('/voice/transcribe', ['audio' => $this->recording()])
            ->assertStatus(503);
    }
}
