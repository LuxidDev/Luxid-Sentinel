<?php

use Luxid\Foundation\Application;
use Luxid\Routing\RouteBuilder;
use Luxid\Nodes\Route;

if (!function_exists('route')) {
    /**
     * Global helper function to create a new fluent route
     */
    function route(string $name): RouteBuilder
    {
        // Check if Application::$app is initialized
        if (!isset(Application::$app) || Application::$app === null) {
            throw new \RuntimeException(
                'Application not initialized. Make sure to create an Application instance before defining routes.'
            );
        }

        $router = Application::$app->router;
        return new RouteBuilder($router, $name);
    }
}

if (!function_exists('route_group')) {
    /**
     * Global helper function for route grouping
     * Alias for Route::group()
     */
    function route_group(array $options, callable $callback): void
    {
        Route::group($options, $callback);
    }
}
