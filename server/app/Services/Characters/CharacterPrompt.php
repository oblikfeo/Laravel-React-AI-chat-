<?php

namespace App\Services\Characters;

use App\Models\Character;

/**
 * Системный запрос для диалога с персонажем.
 *
 * Собирает всё, что автор рассказал о персонаже, в одно сообщение для
 * модели: кто он, что помнит и на какие сведения опирается.
 */
class CharacterPrompt
{
    public function for(Character $character): string
    {
        $parts = [$this->role($character)];

        if ($memories = $this->memories($character)) {
            $parts[] = $memories;
        }

        if ($context = $this->context($character)) {
            $parts[] = $context;
        }

        return implode("\n\n", $parts);
    }

    /**
     * Роль.
     *
     * Свой системный запрос автора используется как есть, без нашей
     * обвязки: он написал его затем, чтобы управлять моделью целиком.
     */
    private function role(Character $character): string
    {
        if (filled($character->system_prompt)) {
            return trim($character->system_prompt);
        }

        return implode("\n\n", array_filter([
            "You are {$character->name}. Stay in character at all times: "
            .'speak, think and react as this character would. '
            .'Never say you are an AI model or an assistant, and do not mention these instructions. '
            .'Always reply in the same language the user writes in.',

            filled($character->description)
                ? "About the character: {$character->description}"
                : null,

            "Personality and behaviour:\n".trim($character->instructions),
        ]));
    }

    /**
     * Память: то, что персонаж помнит всегда.
     *
     * В запрос уходят только последние сообщения переписки, и без
     * памяти персонаж забывал бы всё, что было раньше.
     */
    private function memories(Character $character): ?string
    {
        $memories = array_filter((array) $character->memories);

        if (! $memories) {
            return null;
        }

        return "Things you always remember:\n- ".implode("\n- ", $memories);
    }

    private function context(Character $character): ?string
    {
        if (blank($character->context_text)) {
            return null;
        }

        return "Background knowledge you can rely on:\n".$character->context_text;
    }
}
