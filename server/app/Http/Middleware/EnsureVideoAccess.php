<?php

namespace App\Http\Middleware;

use App\Services\Studio\VideoLimiter;
use App\Services\Studio\VideoStudio;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Пускает к видео только тех, кому оно положено.
 *
 * Условия общие для ролика по описанию и для оживления картинки:
 * раздел подключён, тариф платный, дневной лимит не исчерпан.
 */
class EnsureVideoAccess
{
    public function __construct(
        private readonly VideoStudio $studio,
        private readonly VideoLimiter $limiter,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $this->studio->isAvailable()) {
            return back()->with('error', 'Studio is not available yet. It is coming soon.');
        }

        if (! $this->limiter->hasAccess($user)) {
            return back()->with('error', 'Video is available on paid plans.');
        }

        if (! $this->limiter->allows($user)) {
            return back()->with('error', "You have reached today's video limit. It resets tomorrow.");
        }

        return $next($request);
    }
}
