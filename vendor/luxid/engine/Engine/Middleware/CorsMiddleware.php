<?php

namespace Luxid\Middleware;

use Luxid\Foundation\Application;

class CorsMiddleware extends BaseMiddleware
{
    public array $allowedOrigins = ['*']; // allow all by default, can restrict to your frontend URLs

    public function execute()
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';

        // Allow origin if in allowed list or '*' is used
        if (in_array($origin, $this->allowedOrigins) || $this->allowedOrigins[0] === '*') {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
            header('Access-Control-Allow-Credentials: true');
        }

        // Handle preflight OPTIONS requests
        if (Application::$app->request->method() === 'options') {
            http_response_code(200);
            exit; // stop further execution for preflight
        }

        // Nothing else to do; execution continues to the next middleware or action
    }
}
