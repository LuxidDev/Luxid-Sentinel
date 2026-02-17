<?php

namespace Luxid\Nodes;

use Luxid\Foundation\Application;
use Luxid\Http\Request as HttpRequest;

class Request
{
    /**
     * Get the current Request instance from the application container
     *
     * @return HttpRequest
     */
    protected static function instance(): HttpRequest
    {
        if (!Application::$app || !Application::$app->request) {
            throw new \RuntimeException("No request instance available in Application.");
        }

        return Application::$app->request;
    }

    /**
     * Get query parameter(s) (from $_GET)
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public static function query(string $key = null, $default = null)
    {
        return self::instance()->query($key, $default);
    }

    /**
     * Get input/body parameter(s) (from POST/PUT/PATCH)
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    public static function input(string $key = null, $default = null)
    {
        return self::instance()->input($key, $default);
    }

    /**
     * Get all request parameters (merged GET + body)
     *
     * @return array
     */
    public static function all(): array
    {
        return self::instance()->all();
    }

    /**
     * Check if request contains a specific key
     *
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return self::instance()->has($key);
    }

    /**
     * Get request method (get, post, put, patch, delete)
     *
     * @return string
     */
    public static function method(): string
    {
        return self::instance()->method();
    }

    /**
     * Shortcut to check if GET
     */
    public static function isGet(): bool
    {
        return self::instance()->isGet();
    }

    /**
     * Shortcut to check if POST
     */
    public static function isPost(): bool
    {
        return self::instance()->isPost();
    }

    /**
     * Shortcut to check if JSON request
     */
    public static function isJson(): bool
    {
        return self::instance()->isJson();
    }
}
