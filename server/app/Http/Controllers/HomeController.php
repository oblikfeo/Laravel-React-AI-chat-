<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Отображает главную страницу Uncensia (генерация/анимация видео).
     */
    public function __invoke(): Response
    {
        return Inertia::render('Home');
    }
}
