<?php

namespace Luxid\Middleware;

use Luxid\Foundation\Application;
use Luxid\Exceptions\ForbiddenException;

class AuthMiddleware extends BaseMiddleware
{
    public array $publicActivities = [];

    public function __construct(array $publicActivities = [])
    {
        $this->publicActivities = $publicActivities;
    }

    public function execute()
    {
        // Skip if no action is set (can happen with closure routes)
        if (Application::$app->action === null) {
            return;
        }

        // Skip if user is authenticated
        if (!Application::isGuest()) {
            return;
        }

        $currentActivity = Application::$app->action->activity ?? '';

        // Check if this activity is in the public list
        // If publicActivities is empty, NO activities are public (all require auth)
        if (!in_array($currentActivity, $this->publicActivities)) {
            throw new ForbiddenException();
        }
    }
}
