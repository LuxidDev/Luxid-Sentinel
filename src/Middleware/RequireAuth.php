<?php

declare(strict_types=1);

namespace Luxid\Sentinel\Middleware;

use Luxid\Middleware\BaseMiddleware;
use Luxid\Sentinel\AuthManager;
use Luxid\Http\Response;
use Luxid\Exceptions\ForbiddenException;

/**
 * Middleware to require authentication for routes.
 *
 * Protects routes by checking if a user is authenticated.
 * Returns JSON 401 for API requests or throws ForbiddenException for web requests.
 *
 * @package Luxid\Sentinel\Middleware
 */
class RequireAuth extends BaseMiddleware
{
    /**
     * Create a new RequireAuth middleware.
     *
     * @param AuthManager $auth Authentication manager
     * @param Response $response Response instance
     */
    public function __construct(
        protected AuthManager $auth,
        protected Response $response
    ) {}

    /**
     * Execute the middleware.
     *
     * @return void
     *
     * @throws ForbiddenException If user is not authenticated
     */
    public function execute(): void
    {
        if ($this->auth->check()) {
            return;
        }

        // Check if this is an API request
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
        $isApiRequest = strpos($path, '/api/') === 0 ||
                       strpos($acceptHeader, 'application/json') !== false;

        if ($isApiRequest) {
            // Return JSON response for API requests
            $this->response->json([
                'success' => false,
                'message' => 'Unauthenticated. Please log in.',
                'error' => 'Authentication required'
            ], 401);
            exit;
        }

        // Throw exception for web requests (handled by framework)
        throw new ForbiddenException('You must be logged in to access this page.');
    }
}
