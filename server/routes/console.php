<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Подписки с истёкшим сроком переводим на бесплатный тариф.
// Раз в час: точность до часа здесь достаточна.
Schedule::command('subscriptions:expire')->hourly();

// Готовые музыку, эффекты и ролики забираем у провайдера сами: без
// этого работа дошла бы до галереи, только пока открыта Студия.
Schedule::command('studio:collect')->everyMinute()->withoutOverlapping();
