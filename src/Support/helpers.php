<?php

use Luxid\Sentinel\AuthManager;
use Luxid\Foundation\Application;

if (!function_exists('auth')) {
    /**
     * Get the available auth instance.
     *
     * @param string|null $guard
     * @return AuthManager|\Luxid\Sentinel\Contracts\Guard
     *
     * @example
     * ```php
     * // Get auth manager
     * $auth = auth();
     *
     * // Attempt login
     * if (auth()->attempt($credentials)) { ... }
     *
     * // Get current user
     * $user = auth()->user();
     *
     * // Check if authenticated
     * if (auth()->check()) { ... }
     * ```
     */
    function auth(?string $guard = null)
    {
        if (!Application::$app || !Application::$app->has('auth')) {
            throw new RuntimeException(
                'Auth service not available. Make sure SentinelServiceProvider is registered.'
            );
        }

        $manager = Application::$app->get('auth');

        if ($guard !== null) {
            return $manager->guard($guard);
        }

        return $manager;
    }
}
