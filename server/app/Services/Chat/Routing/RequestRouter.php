<?php

namespace App\Services\Chat\Routing;

/**
 * Модель-диспетчер: понимает, что человек хочет от чата.
 */
interface RequestRouter
{
    /**
     * Null — диспетчер не ответил или ответил непонятно; тогда решает
     * запасное правило.
     */
    public function route(RoutingInput $input): ?Route;
}
