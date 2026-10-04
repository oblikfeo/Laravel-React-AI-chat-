<?php

namespace App\Http\Controllers\Feed;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeedItemResource;
use App\Models\Generation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowFeedController extends Controller
{
    /** Сколько работ отдаём за раз. */
    private const PER_PAGE = 36;

    /**
     * Общая лента работ.
     *
     * Показываем то, что люди согласились показать: закрытые работы
     * и работы безцензурных моделей сюда не попадают.
     */
    public function __invoke(Request $request): Response
    {
        $works = Generation::query()
            ->inFeed()
            ->with('user:id,name')
            ->latest('id')
            ->cursorPaginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('Feed/Index', [
            'works' => FeedItemResource::collection($works),
        ]);
    }
}
