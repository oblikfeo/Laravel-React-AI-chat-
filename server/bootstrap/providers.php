<?php

use App\Providers\AiServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\BillingServiceProvider;
use App\Providers\StudioServiceProvider;

return [
    AppServiceProvider::class,
    AiServiceProvider::class,
    BillingServiceProvider::class,
    StudioServiceProvider::class,
];
