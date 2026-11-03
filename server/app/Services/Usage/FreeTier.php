<?php

namespace App\Services\Usage;

use App\Models\Generation;
use App\Models\Guest;
use App\Models\Message;
use App\Models\UsageRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ограничения без подписки: бесплатный тариф и гость.
 *
 * Кроме бюджета здесь два предела. Дневной — сколько раз можно
 * воспользоваться разделом (сообщений, картинок, правок, звуков).
 * И потолок дорогих настроек: длинный звук, большое увеличение,
 * несколько вариантов за раз. Иначе один трёхминутный звук съедает
 * треть месячного бюджета.
 *
 * Подписчиков это не касается: их держит только бюджет тарифа.
 */
class FreeTier
{
    /** Разделы, которые считаются в дневном пределе. */
    public const TOOLS = ['chat', 'image', 'edit', 'audio', 'voice'];

    private const NOUNS = [
        'chat' => 'messages',
        'image' => 'images',
        'edit' => 'image edits',
        'audio' => 'audio',
        'voice' => 'voice input',
    ];

    /**
     * Пределы для этого человека; null — подписчик.
     *
     * @return array{daily: array<string, int>, caps: array<string, mixed>}|null
     */
    public function for(?User $user, ?Guest $guest): ?array
    {
        if ($user) {
            $plan = config("plans.list.{$user->plan}");

            return isset($plan['daily'])
                ? ['daily' => $plan['daily'], 'caps' => $plan['caps'] ?? []]
                : null;
        }

        // Сообщения гостя считает GuestLimiter: там ещё предел по адресу.
        return $guest ? ['daily' => config('guests.daily', []), 'caps' => config('guests.caps', [])] : null;
    }

    /** @return array<string, mixed>|null */
    public function caps(?User $user, ?Guest $guest): ?array
    {
        return $this->for($user, $guest)['caps'] ?? null;
    }

    /** К какому разделу относится операция Студии. */
    public static function toolOf(string $operation): string
    {
        return match ($operation) {
            Generation::OP_GENERATE => 'image',
            Generation::OP_SPEECH, Generation::OP_MUSIC, Generation::OP_EFFECT, Generation::OP_VOICE_CHANGE => 'audio',
            Generation::OP_VIDEO, Generation::OP_ANIMATE => 'video',
            default => 'edit',
        };
    }

    /**
     * Почему нельзя, или null — можно.
     *
     * @param  array<string, mixed>  $input  настройки запроса
     */
    public function denial(?User $user, ?Guest $guest, string $tool, ?string $operation = null, array $input = []): ?string
    {
        $tier = $this->for($user, $guest);

        if (! $tier) {
            return null;
        }

        if ($operation && ($cap = $this->capDenial($tier['caps'], $operation, $input))) {
            return $cap;
        }

        if (! array_key_exists($tool, $tier['daily'])) {
            return null;
        }

        $limit = (int) $tier['daily'][$tool];
        $noun = self::NOUNS[$tool] ?? $tool;

        if ($limit <= 0) {
            return $user
                ? ucfirst($noun).' is available on paid plans.'
                : "Sign up to use {$noun}.";
        }

        if ($this->used($user, $guest, $tool) >= $limit) {
            return "You've used today's free {$noun}. It renews tomorrow"
                .($user ? ' — or upgrade for more.' : ' — or sign up for more.');
        }

        return null;
    }

    /** Сколько процентов дневного предела осталось; null — предела нет. */
    public function percentLeft(?User $user, ?Guest $guest, string $tool): ?int
    {
        $tier = $this->for($user, $guest);

        if (! $tier || ! array_key_exists($tool, $tier['daily'])) {
            return null;
        }

        $limit = (int) $tier['daily'][$tool];

        if ($limit <= 0) {
            return 0;
        }

        return (int) floor(max(0, $limit - $this->used($user, $guest, $tool)) / $limit * 100);
    }

    /** Сколько раз за сегодня уже воспользовались разделом. */
    public function used(?User $user, ?Guest $guest, string $tool): int
    {
        if ($tool === 'chat') {
            return Message::query()
                ->where('role', Message::ROLE_USER)
                ->where('created_at', '>=', today())
                ->whereHas('chat', fn (Builder $chat) => $this->owned($chat, $user, $guest))
                ->count();
        }

        if ($tool === 'voice') {
            return $this->owned(UsageRecord::query(), $user, $guest)
                ->where('category', UsageRecord::CATEGORY_VOICE)
                ->where('created_at', '>=', today())
                ->count();
        }

        $operations = match ($tool) {
            'image' => [Generation::OP_GENERATE],
            'audio' => [Generation::OP_SPEECH, Generation::OP_MUSIC, Generation::OP_EFFECT, Generation::OP_VOICE_CHANGE],
            'video' => [Generation::OP_VIDEO, Generation::OP_ANIMATE],
            default => [Generation::OP_EDIT, Generation::OP_COMBINE, Generation::OP_UPSCALE, Generation::OP_BACKGROUND],
        };

        // Несостоявшаяся работа попытку не сжигает: человек не
        // виноват, что у провайдера не получилось.
        return $this->owned(Generation::query(), $user, $guest)
            ->whereIn('operation', $operations)
            ->where('status', '!=', Generation::STATUS_FAILED)
            ->where('created_at', '>=', today())
            ->count();
    }

    /**
     * Дорогая настройка, недоступная без подписки.
     *
     * @param  array<string, mixed>  $caps
     * @param  array<string, mixed>  $input
     */
    private function capDenial(array $caps, string $operation, array $input): ?string
    {
        $seconds = isset($input['duration']) ? (int) $input['duration'] : null;

        return match (true) {
            $operation === Generation::OP_GENERATE
                && isset($caps['variants']) && (int) ($input['variants'] ?? 1) > $caps['variants'] => 'Without a subscription images are created one at a time.',

            $operation === Generation::OP_UPSCALE
                && isset($caps['upscale_scales']) && ! in_array((int) ($input['scale'] ?? 2), $caps['upscale_scales'], true) => 'Larger upscaling is available on paid plans.',

            $operation === Generation::OP_MUSIC
                && isset($caps['music_seconds']) && ($seconds ?? $this->defaultSeconds('music', $input)) > $caps['music_seconds'] => "Without a subscription music is up to {$caps['music_seconds']} seconds.",

            $operation === Generation::OP_EFFECT
                && isset($caps['effect_models']) && ! in_array($input['model'] ?? config('studio.effects.default_model'), $caps['effect_models'], true) => 'This sound effect model is available on paid plans.',

            $operation === Generation::OP_EFFECT
                && isset($caps['effect_seconds']) && ($seconds ?? $this->defaultSeconds('effects', $input)) > $caps['effect_seconds'] => "Without a subscription sound effects are up to {$caps['effect_seconds']} seconds.",

            $operation === Generation::OP_SPEECH
                && isset($caps['speech_characters']) && mb_strlen((string) ($input['text'] ?? '')) > $caps['speech_characters'] => "Without a subscription voice-overs are up to {$caps['speech_characters']} characters.",

            $operation === Generation::OP_VOICE_CHANGE && ($caps['voice_change'] ?? true) === false => 'Voice changer is available on paid plans.',

            default => null,
        };
    }

    /** Длительность по умолчанию у модели звука, если человек её не задал. */
    private function defaultSeconds(string $group, array $input): int
    {
        $model = $input['model'] ?? config("studio.{$group}.default_model");

        return (int) config("studio.{$group}.models.{$model}.default_duration", 0);
    }

    /**
     * Записи этого человека: пользователя или гостя.
     *
     * @template T of Builder
     *
     * @param  T  $query
     * @return T
     */
    private function owned(Builder $query, ?User $user, ?Guest $guest): Builder
    {
        return $user
            ? $query->where('user_id', $user->id)
            : $query->where('guest_id', $guest?->id ?? 0)->whereNull('user_id');
    }
}
