<?php

namespace App\Actions\Characters;

use App\Services\Ai\AiChatProvider;
use App\Services\Ai\ModelCatalog;
use RuntimeException;

/**
 * Дописывает за автора описание или инструкции персонажа.
 *
 * Кнопка «Auto-Generate» в форме: по тому, что уже заполнено, модель
 * предлагает недостающий текст. Автор волен его править или стереть.
 */
class WriteCharacterText
{
    public const DESCRIPTION = 'description';
    public const INSTRUCTIONS = 'instructions';

    public function __construct(private readonly AiChatProvider $provider)
    {
    }

    /**
     * @param  array{name?: ?string, description?: ?string, instructions?: ?string}  $draft
     */
    public function handle(string $target, array $draft): string
    {
        if (! $this->provider->isConfigured()) {
            throw new RuntimeException('Текстовая модель не подключена.');
        }

        $known = collect([
            'Name' => $draft['name'] ?? null,
            'Description' => $draft['description'] ?? null,
            'Personality' => $draft['instructions'] ?? null,
        ])
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value, $label) => "{$label}: ".trim((string) $value))
            ->implode("\n");

        $answer = $this->provider->complete(
            [
                ['role' => 'system', 'content' => $this->task($target)],
                ['role' => 'user', 'content' => $known],
            ],
            ModelCatalog::providerModel(
                ModelCatalog::resolve(config('characters.writer_model')),
            ),
        );

        $limit = (int) config("characters.limits.{$target}");

        return mb_substr(trim($answer->content, " \t\n\r\"'"), 0, $limit);
    }

    private function task(string $target): string
    {
        $common = 'Reply with the text only: no headings, no quotes, no comments. '
            .'Write in the same language as the details you are given.';

        return match ($target) {
            self::DESCRIPTION => 'You write short public descriptions for chat characters. '
                .'From the details below write one or two sentences that tell a visitor '
                .'who this character is and why talking to them is interesting. '.$common,

            default => 'You write personality instructions for chat characters. '
                .'From the details below describe in the second person ("You are…") '
                .'who the character is, how they speak, what they care about and how '
                .'they treat the person they talk to. Six to ten sentences. '.$common,
        };
    }
}
