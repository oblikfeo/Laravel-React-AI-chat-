<?php

namespace App\Http\Middleware;

use App\Services\Studio\VideoStudio;
use App\Services\Usage\UsageGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Пускает к видео только тех, кому оно положено.
 *
 * Условия общие для ролика по описанию и для оживления картинки:
 * раздел подключён, видео есть на тарифе, бюджет не исчерпан.
 */
class EnsureVideoAccess
{
    public function __construct(
        private readonly VideoStudio $studio,
        private readonly UsageGate $gate,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->studio->isAvailable()) {
            return back()->with('error', 'Studio is not available yet. It is coming soon.');
        }

        if ($denial = $this->gate->video($request->user())) {
            return back()->with('error', $denial);
        }

        return $next($request);
    }
}
