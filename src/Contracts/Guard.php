<?php

declare(strict_types=1);

namespace Luxid\Sentinel\Contracts;

/**
 * Contract for authentication guards.
 *
 * Guards define how users are authenticated and retrieved for each request.
 * Sentinel provides SessionGuard by default, but this contract allows
 * for future expansion (API tokens, OAuth, etc.).
 *
 * @package Luxid\Sentinel\Contracts
 */
interface Guard
{
    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check(): bool;

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest(): bool;

    /**
     * Get the currently authenticated user.
     *
     * @return Authenticatable|null
     */
    public function user(): ?Authenticatable;

    /**
     * Get the ID of the currently authenticated user.
     *
     * @return int|string|null
     */
    public function id();

    /**
     * Validate a user's credentials.
     *
     * @param array $credentials
     * @return bool
     */
    public function validate(array $credentials = []): bool;

    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param array $credentials
     * @param bool $remember
     * @return bool
     */
    public function attempt(array $credentials = [], bool $remember = false): bool;

    /**
     * Log a user into the application.
     *
     * @param Authenticatable $user
     * @param bool $remember
     * @return bool
     */
    public function login(Authenticatable $user, bool $remember = false): bool;

    /**
     * Log the user out of the application.
     *
     * @return void
     */
    public function logout(): void;

    /**
     * Get the entity provider instance.
     *
     * @return mixed
     */
    public function getProvider();
}
