<?php

namespace App\Services\Admin;

use App\Models\UsageRecord;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Все модели сервиса для админки: где подключены, у какого
 * провайдера, сколько стоят и сколько на них ушло за период.
 *
 * Только для администратора: здесь настоящие названия моделей у
 * провайдеров, пользователю они не показываются никогда.
 */
class ModelDirectory
{
    /**
     * @return array<int, array{title: string, provider: string, configured: bool, rows: array<int, array<string, mixed>>}>
     */
    public function sections(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $usage = UsageRecord::query()
            ->between($from, $to)
            ->selectRaw('category, model_key, provider_model, COUNT(*) as requests, SUM(cost) as spend')
            ->groupBy('category', 'model_key', 'provider_model')
            ->get();

        $text = ucfirst((string) config('ai.provider'));
        $textReady = filled(config('ai.providers.'.config('ai.provider').'.api_key'));
        $studio = ucfirst((string) config('studio.provider'));
        $studioReady = filled(config('studio.api_key'));

        $sections = [
            [
                'title' => 'Chat',
                'provider' => $text,
                'configured' => $textReady,
                'rows' => $this->chatModels(),
            ],
            [
                'title' => 'Service calls',
                'provider' => "{$text}, {$studio}",
                'configured' => $textReady && $studioReady,
                'rows' => $this->serviceModels(),
            ],
            [
                'title' => 'Studio · Images',
                'provider' => $studio,
                'configured' => $studioReady,
                'rows' => $this->group('studio.models', UsageRecord::CATEGORY_IMAGE, 'Studio → Image, chat pictures'),
            ],
            [
                'title' => 'Studio · Edit',
                'provider' => $studio,
                'configured' => $studioReady,
                'rows' => $this->editTools(),
            ],
            [
                'title' => 'Studio · Audio',
                'provider' => $studio,
                'configured' => $studioReady,
                'rows' => [
                    ...$this->group('studio.speech.models', UsageRecord::CATEGORY_AUDIO, 'Studio → Audio → Voice'),
                    ...$this->group('studio.music.models', UsageRecord::CATEGORY_AUDIO, 'Studio → Audio → Music'),
                    ...$this->group('studio.effects.models', UsageRecord::CATEGORY_AUDIO, 'Studio → Audio → Sound effect'),
                    $this->row(
                        key: 'voice_changer',
                        label: 'Voice changer',
                        providerModel: config('studio.voice_changer.provider_model'),
                        price: self::priceText(config('studio.voice_changer')),
                        usedIn: 'Studio → Audio → Voice changer',
                        match: ['category' => UsageRecord::CATEGORY_AUDIO, 'model_key' => 'voice_changer'],
                        estimate: true,
                    ),
                ],
            ],
            [
                'title' => 'Studio · Video',
                'provider' => $studio,
                'configured' => $studioReady,
                'rows' => $this->videoModels(),
            ],
        ];

        foreach ($sections as &$section) {
            $section['rows'] = array_map(fn (array $row) => $this->withUsage($row, $usage), $section['rows']);
        }

        return $sections;
    }

    /** @return array<int, array<string, mixed>> */
    private function chatModels(): array
    {
        return collect(config('models.list'))
            ->map(fn (array $model, string $key) => $this->row(
                key: $key,
                label: $model['label'],
                providerModel: $model['provider_model'],
                price: isset($model['price'])
                    ? sprintf('$%s in · $%s out / 1M tokens', self::number($model['price']['input']), self::number($model['price']['output']))
                    : '—',
                usedIn: collect([
                    'Chat',
                    $key === config('models.default') ? 'default' : null,
                    $key === config('guests.model') ? 'guests' : null,
                    ($model['vision'] ?? false) ? 'reads images' : null,
                    isset($model['fallback']) ? "falls back to {$model['fallback']}" : null,
                ])->filter()->implode(' · '),
                match: ['category' => UsageRecord::CATEGORY_CHAT, 'model_key' => $key],
            ))
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function serviceModels(): array
    {
        $router = config('ai.router.price');
        $writer = config('characters.writer_model');

        return [
            $this->row(
                key: 'router',
                label: 'Request router',
                providerModel: config('ai.router.model'),
                price: sprintf('$%s in · $%s out / 1M tokens', self::number($router['input']), self::number($router['output'])),
                usedIn: 'Reads every chat message and decides: text, picture, audio or video',
                match: ['category' => UsageRecord::CATEGORY_CHAT, 'model_key' => 'router'],
                estimate: true,
            ),
            $this->row(
                key: 'translator',
                label: 'Prompt translator',
                providerModel: config('studio.translate_model'),
                price: sprintf('$%s in · $%s out / 1M tokens', self::number(config('studio.translate_price.input')), self::number(config('studio.translate_price.output'))),
                usedIn: 'Translates Studio prompts to English',
                match: ['category' => UsageRecord::CATEGORY_SERVICE, 'provider_model' => config('studio.translate_model')],
            ),
            $this->row(
                key: 'writer',
                label: 'Character writer',
                providerModel: config("models.list.{$writer}.provider_model"),
                price: 'as the chat model',
                usedIn: 'Auto-Generate in the character editor',
                match: ['category' => UsageRecord::CATEGORY_SERVICE, 'model_key' => $writer],
            ),
            $this->row(
                key: 'transcription',
                label: 'Voice input',
                providerModel: config('ai.transcription.model'),
                price: '$'.self::number((float) config('ai.transcription.cost_per_second')).' / sec',
                usedIn: 'Microphone button in the chat',
                match: ['category' => UsageRecord::CATEGORY_VOICE, 'model_key' => 'transcription'],
            ),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function editTools(): array
    {
        $costs = config('studio.edit.costs');
        $model = config('studio.edit.model');

        return [
            $this->row(
                key: 'edit',
                label: 'Edit & combine',
                providerModel: $model,
                price: sprintf('$%s edit · $%s combine', self::number($costs['edit']), self::number($costs['combine'])),
                usedIn: 'Studio → Edit, chat edits',
                match: ['category' => UsageRecord::CATEGORY_EDIT, 'provider_model' => $model],
            ),
            $this->row(
                key: 'upscale',
                label: 'Upscale',
                providerModel: 'upscale',
                price: collect($costs['upscale'])->map(fn ($cost, $scale) => "×{$scale} \$".self::number($cost))->implode(' · '),
                usedIn: 'Studio → Edit → Upscale',
                match: ['category' => UsageRecord::CATEGORY_EDIT, 'provider_model' => 'upscale'],
            ),
            $this->row(
                key: 'background_remove',
                label: 'Remove background',
                providerModel: 'background-remove',
                price: '$'.self::number($costs['background_remove']).' / image',
                usedIn: 'Studio → Edit → Remove background',
                match: ['category' => UsageRecord::CATEGORY_EDIT, 'provider_model' => 'background-remove'],
                estimate: true,
            ),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function videoModels(): array
    {
        return collect(config('studio.video.models'))
            ->map(function (array $model, string $key) {
                $prices = collect($model['costs'])->flatten();

                return $this->row(
                    key: $key,
                    label: $model['label'],
                    providerModel: collect([$model['provider_model'], $model['provider_model_image'] ?? null])->filter()->implode(', '),
                    price: sprintf('$%s–$%s / clip', self::number($prices->min()), self::number($prices->max())),
                    usedIn: 'Studio → Video (paid plans)',
                    match: ['category' => UsageRecord::CATEGORY_VIDEO, 'model_key' => $key],
                );
            })
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function group(string $config, string $category, string $usedIn): array
    {
        return collect(config($config))
            ->map(fn (array $model, string $key) => $this->row(
                key: $key,
                label: $model['label'],
                providerModel: $model['provider_model'],
                price: self::priceText($model),
                usedIn: $usedIn.(($model['paid'] ?? false) ? ' · paid plans' : ''),
                match: ['category' => $category, 'model_key' => $key],
            ))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, string>  $match  какие записи журнала относятся к модели
     * @return array<string, mixed>
     */
    private function row(
        string $key,
        string $label,
        ?string $providerModel,
        string $price,
        string $usedIn,
        array $match,
        bool $estimate = false,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'providerModel' => $providerModel,
            'price' => $price,
            // Цена не из каталога провайдера, а наша оценка.
            'estimate' => $estimate,
            'usedIn' => $usedIn,
            'match' => $match,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  Collection<int, UsageRecord>  $usage
     * @return array<string, mixed>
     */
    private function withUsage(array $row, Collection $usage): array
    {
        $match = $row['match'];
        unset($row['match']);

        $mine = $usage->filter(fn ($line) => $line->category === $match['category']
            && (! isset($match['model_key']) || $line->model_key === $match['model_key'])
            && (! isset($match['provider_model']) || $line->provider_model === $match['provider_model']));

        return [
            ...$row,
            'requests' => (int) $mine->sum('requests'),
            'spend' => round((float) $mine->sum('spend'), 4),
        ];
    }

    /**
     * Цена по описанию модели в config/studio.php.
     *
     * @param  array<string, mixed>  $model
     */
    private static function priceText(array $model): string
    {
        return match (true) {
            isset($model['cost']) => '$'.self::number($model['cost']).' / item',
            isset($model['cost_per_second']) => '$'.self::number($model['cost_per_second']).' / sec',
            isset($model['cost_per_million']) => '$'.self::number($model['cost_per_million']).' / 1M chars',
            isset($model['costs']) => sprintf('$%s–$%s by length', self::number(min($model['costs'])), self::number(max($model['costs']))),
            default => '—',
        };
    }

    /** Без лишних нулей: 0.01, 0.002875, 3.5. */
    private static function number(float|int $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 6, '.', ''), '0'), '.');
    }
}
