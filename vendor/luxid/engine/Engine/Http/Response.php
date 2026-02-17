<?php

namespace Luxid\Http;

use Luxid\Foundation\Application;

class Response
{
    public function setStatusCode(int $code)
    {
        http_response_code($code);
    }

    public function warp(string $url)
    {
        header('Location: ' . $url);
    }

    /**
     * Send JSON response
     */
    public function json($data, int $statusCode = 200): string
    {
        $this->setStatusCode($statusCode);
        header('Content-Type: application/json');
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Send successful JSON response
     */
    public function success($data = null, string $message = 'Success', int $statusCode = 200): string
    {
        return $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Send error JSON response
     */
    public function error(string $message = 'Error', $errors = null, int $statusCode = 400): string
    {
        return $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }

    /**
     * Redirect with flash message
     */
    public function redirectWith(string $url, string $key, string $message)
    {
        if (Application::$app) {
            Application::$app->session->setFlash($key, $message);
        }
        $this->warp($url);
    }
}
