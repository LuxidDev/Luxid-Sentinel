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
    // Debug: Check if we have a session
    error_log("=== RequireAuth Middleware ===");
    error_log("Session ID: " . session_id());
    error_log("Session data: " . json_encode($_SESSION));

    // Check if the auth manager has a user
    error_log("Auth check: " . ($this->auth->check() ? 'true' : 'false'));

    $user = $this->auth->user();
    error_log("User: " . ($user ? json_encode(['id' => $user->id, 'email' => $user->email]) : 'null'));

    if ($this->auth->check()) {
      error_log("User is authenticated, proceeding...");
      return;
    }

    error_log("User not authenticated, checking request type...");

    $path = $_SERVER['REQUEST_URI'] ?? '/';
    $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
    $isApiRequest = strpos($path, '/api/') === 0 ||
      strpos($acceptHeader, 'application/json') !== false;

    error_log("Is API request: " . ($isApiRequest ? 'true' : 'false'));

    if ($isApiRequest) {
      $this->response->json([
        'success' => false,
        'message' => 'Unauthenticated. Please log in.',
        'error' => 'Authentication required'
      ], 401);
      exit;
    }

    throw new ForbiddenException('You must be logged in to access this page.');
  }
}
