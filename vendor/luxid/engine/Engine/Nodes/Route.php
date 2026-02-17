<?php

namespace Luxid\Nodes;

use Luxid\Foundation\Application;
use Luxid\Routing\RouteBuilder;

class Route
{
    /**
     * Create a new fluent route
     */
    public static function make(string $name): RouteBuilder
    {
        if (!isset(Application::$app) || Application::$app === null) {
            throw new \RuntimeException(
                'Application not initialized. Make sure to create an Application instance before defining routes.'
            );
        }

        $router = Application::$app->router;
        return new RouteBuilder($router, $name);
    }

    /**
     * Alias for make()
     */
    public static function name(string $name): RouteBuilder
    {
        return self::make($name);
    }

    /**
     * Create a route group with shared configuration
     */
    public static function group(array $options, callable $callback): void
    {
        $router = Application::$app->router;
        $router->group($options, $callback);
    }

    /**
     * Get all registered routes (for debugging)
     */
    public static function all(): array
    {
        $router = Application::$app->router;
        return $router->getRoutesForInspection();
    }

    /**
     * Magic static method for Route::todos() syntax
     */
    public static function __callStatic($method, $args)
    {
        return self::make($method);
    }
}
