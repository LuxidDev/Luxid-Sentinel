<?php

namespace Luxid\Middleware;

/**
 * Middleware for explicitly public routes (no authentication checks)
 * Alternative to AuthMiddleware with publicActivities array
 */
class PublicMiddleware extends BaseMiddleware
{
    public function execute()
    {
        // Public routes - no authentication required
        // This middleware does nothing, just marks route as public
        return;
    }
}
