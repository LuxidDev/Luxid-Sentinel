<?php

namespace Luxid\Nodes;

use Luxid\Foundation\Application;

class Response
{
    protected static function instance(): \Luxid\Http\Response
    {
        return Application::$app->response;
    }

    public static function json($data, int $statusCode = 200): string
    {
        return self::instance()->json($data, $statusCode);
    }

    public static function success($data = null, string $message = 'Success', int $statusCode = 200): string
    {
        return self::instance()->success($data, $message, $statusCode);
    }

    public static function error(string $message = 'Error', $errors = null, int $statusCode = 400): string
    {
        return self::instance()->error($message, $errors, $statusCode);
    }

    public static function redirectWith(string $url, string $key, string $message)
    {
        return self::instance()->redirectWith($url, $key, $message);
    }
}
