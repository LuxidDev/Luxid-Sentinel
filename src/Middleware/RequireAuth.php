<?php

declare(strict_types=1);

namespace Luxid\Haven\Middleware;

use Luxid\Contracts\Auth\AuthManager;
use Luxid\Exceptions\UnauthorizedException;
use Luxid\Foundation\Application;
use Luxid\Middleware\BaseMiddleware;

/**
 * Requires an authenticated user.
 *
 * Rejection is signalled by throwing, which lets the kernel render the failure
 * in the right format and flush it. The previous implementation built a 401
 * body, discarded it and called `exit`, so the client received a blank 200.
 *
 * @package Luxid\Haven\Middleware
 */
class RequireAuth extends BaseMiddleware
{
    /**
     * @param AuthManager|null $auth Auth manager, or null to resolve from the kernel
     */
    public function __construct(protected ?AuthManager $auth = null)
    {
    }

    /**
     * Reject the request unless a user is signed in.
     *
     * @throws UnauthorizedException When no user is signed in
     */
    public function execute(): void
    {
        $auth = $this->auth ?? Application::$app->auth;

        if ($auth !== null && $auth->check()) {
            return;
        }

        if ($auth === null && Application::$app->user !== null) {
            return;
        }

        throw new UnauthorizedException('Unauthenticated. Please log in.');
    }
}
