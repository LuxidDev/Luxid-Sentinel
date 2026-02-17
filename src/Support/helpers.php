<?php

use Luxid\Sentinel\Sentinel;
use Luxid\Foundation\Application;

if (!function_exists('auth')) {
    /**
     * Get the available auth instance.
     *
     * @param string|null $guard
     * @return \Luxid\Sentinel\AuthManager|\Luxid\Sentinel\Contracts\Guard
     *
     * @throws RuntimeException If auth service is not available
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
        static $manager = null;

        if (!Application::$app) {
            throw new RuntimeException('Application not initialized.');
        }

        if ($manager === null) {
            // Try to get auth manager from Sentinel static registry
            try {
                $manager = Sentinel::getManager();
            } catch (\RuntimeException $e) {
                throw new RuntimeException(
                    'Auth service not available. Make sure SentinelServiceProvider::boot() was called.',
                    0,
                    $e
                );
            }
        }

        if ($guard !== null) {
            return $manager->guard($guard);
        }

        return $manager;
    }
}
