<?php

namespace App\Services\Chat\Routing;

/**
 * Диспетчер без ключа провайдера: всегда отдаёт решение правилу.
 */
final class UnavailableRouter implements RequestRouter
{
    public function route(RoutingInput $input): ?Route
    {
        return null;
    }
}
