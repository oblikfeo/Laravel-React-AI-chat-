<?php

namespace App\Http\Middleware;

use App\Services\Guests\CurrentGuest;
use App\Services\Studio\AudioStudio;
use App\Services\Studio\ImageGenerator;
use App\Services\Studio\StudioLimiter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Пускает к инструментам Студии: раздел подключён и дневной лимит не
 * исчерпан.
 *
 * Параметр — какой провайдер нужен: image (картинки, правка, озвучка)
 * или audio (музыка, эффекты, смена голоса).
 */
class EnsureStudioAccess
{
    public function __construct(
        private readonly ImageGenerator $images,
        private readonly AudioStudio $audio,
        private readonly StudioLimiter $limiter,
    ) {}

    public function handle(Request $request, Closure $next, string $tool = 'image'): Response
    {
        $available = $tool === 'audio'
            ? $this->audio->isAvailable()
            : $this->images->isAvailable();

        if (! $available) {
            return back()->with('error', 'Studio is not available yet. It is coming soon.');
        }

        $user = $request->user();

        if (! $this->limiter->allows($user, CurrentGuest::get($request))) {
            return back()->with('error', $user
                ? "You have reached today's limit. It resets tomorrow."
                : "You have reached today's free limit. Sign up to create more.");
        }

        return $next($request);
    }
}
