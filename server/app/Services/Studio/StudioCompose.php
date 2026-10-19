<?php

namespace App\Services\Studio;

use App\Http\Resources\GenerationResource;
use App\Models\Attachment;
use App\Models\Generation;
use App\Models\Guest;
use App\Models\User;
use App\Services\Guests\ChatOwnership;
use Illuminate\Http\Request;

/**
 * С чем открыть Студию: раздел, режим, описание и картинка.
 *
 * Так в Студию приходят из чата (карточка «сделать в Студии», меню
 * картинки) и из общей ленты (кнопка правки). Адрес:
 * /studio?compose=edit&work=12 или /studio?compose=audio&mode=music&prompt=…
 *
 * Картинку можно взять свою работу, работу из общей ленты или фото,
 * приложенное в своём же чате. Чужое скрытое по номеру не открыть.
 */
class StudioCompose
{
    private const TABS = ['image', 'edit', 'audio', 'video'];

    /**
     * @return array<string, mixed>|null
     */
    public function fromRequest(Request $request, ?User $user, ?Guest $guest): ?array
    {
        $tab = $request->query('compose');

        if (! in_array($tab, self::TABS, true)) {
            return null;
        }

        $work = $this->work($request->integer('work'), $user, $guest);

        return [
            'tab' => $tab,
            'mode' => is_string($request->query('mode')) ? $request->query('mode') : null,
            'prompt' => mb_substr((string) $request->query('prompt', ''), 0, 2000),
            'work' => $work ? new GenerationResource($work) : null,
            'attachment' => $this->attachment($request),
        ];
    }

    private function work(int $id, ?User $user, ?Guest $guest): ?Generation
    {
        if (! $id) {
            return null;
        }

        return Generation::query()
            ->whereKey($id)
            ->where('kind', Generation::KIND_IMAGE)
            ->whereNotNull('path')
            ->where(fn ($query) => $query
                ->where(fn ($own) => $own->ownedBy($user, $guest))
                ->orWhere(fn ($shared) => $shared->inFeed()))
            ->first();
    }

    /**
     * @return array{name: string, mime: string, url: string}|null
     */
    private function attachment(Request $request): ?array
    {
        $attachment = $request->integer('attachment')
            ? Attachment::with('message.chat')->find($request->integer('attachment'))
            : null;

        if (! $attachment?->isImage() || ! ChatOwnership::owns($request, $attachment->message->chat)) {
            return null;
        }

        return [
            'name' => $attachment->name,
            'mime' => $attachment->mime,
            'url' => route('attachments.show', $attachment),
        ];
    }
}
