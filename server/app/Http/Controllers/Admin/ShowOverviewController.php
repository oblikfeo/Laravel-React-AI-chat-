<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\Overview;
use Inertia\Inertia;
use Inertia\Response;

class ShowOverviewController extends Controller
{
    /**
     * Главная админки: деньги, подписки, пользователи и расходы на
     * провайдеров за месяц.
     */
    public function __invoke(Overview $overview): Response
    {
        return Inertia::render('Admin/Overview', [
            'overview' => $overview->build(),
        ]);
    }
}
