<?php

namespace App\Http\Middleware;

use App\Http\Resources\ChatResource;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Данные, доступные на всех страницах.
     *
     * Список чатов нужен боковому меню, поэтому он общий.
     * Загружается лениво: Inertia запросит его только при необходимости.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),

            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                ] : null,
            ],

            'sidebarChats' => fn () => $request->user()
                ? ChatResource::collection($request->user()->chats()->limit(30)->get())
                : [],

            'flash' => [
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
