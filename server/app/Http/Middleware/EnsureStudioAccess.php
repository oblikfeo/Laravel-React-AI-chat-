<?php

namespace App\Http\Middleware;

use App\Models\Generation;
use App\Services\Guests\CurrentGuest;
use App\Services\Studio\AudioStudio;
use App\Services\Studio\ImageGenerator;
use App\Services\Usage\UsageGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Пускает к инструментам Студии: раздел подключён, а запрос
 * укладывается в пределы — дневные без подписки и бюджет тарифа с
 * учётом цены этого запроса.
 *
 * Параметр — какой провайдер нужен: image (картинки, правка, озвучка)
 * или audio (музыка, эффекты, смена голоса).
 */
class EnsureStudioAccess
{
    /** Какую операцию запускает каждый адрес. Правка — по полю operation. */
    private const OPERATIONS = [
        'studio.store' => Generation::OP_GENERATE,
        'studio.speech' => Generation::OP_SPEECH,
        'studio.music' => Generation::OP_MUSIC,
        'studio.effect' => Generation::OP_EFFECT,
        'studio.voice-change' => Generation::OP_VOICE_CHANGE,
    ];

    public function __construct(
        private readonly ImageGenerator $images,
        private readonly AudioStudio $audio,
        private readonly UsageGate $gate,
    ) {}

    public function handle(Request $request, Closure $next, string $tool = 'image'): Response
    {
        $available = $tool === 'audio'
            ? $this->audio->isAvailable()
            : $this->images->isAvailable();

        if (! $available) {
            return back()->with('error', 'Studio is not available yet. It is coming soon.');
        }

        $operation = $request->routeIs('studio.edit')
            ? (string) $request->input('operation', Generation::OP_EDIT)
            : self::OPERATIONS[$request->route()?->getName()] ?? Generation::OP_GENERATE;

        $denial = $this->gate->studio(
            $request->user(),
            CurrentGuest::get($request),
            $operation,
            $request->except(['images', 'audio', 'files']),
        );

        if ($denial) {
            return back()->with('error', $denial);
        }

        return $next($request);
    }
}
