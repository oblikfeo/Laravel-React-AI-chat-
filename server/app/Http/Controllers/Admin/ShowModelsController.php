<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\ModelDirectory;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

class ShowModelsController extends Controller
{
    /**
     * Какие модели где подключены, сколько стоят и сколько на них ушло
     * с начала месяца.
     */
    public function __invoke(ModelDirectory $directory): Response
    {
        $from = CarbonImmutable::now()->startOfMonth();

        return Inertia::render('Admin/Models', [
            'month' => $from->format('F Y'),
            'sections' => $directory->sections($from, $from->addMonth()),
        ]);
    }
}
