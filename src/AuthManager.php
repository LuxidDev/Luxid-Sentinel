<?php

declare(strict_types=1);

namespace Luxid\Sentinel;

use Luxid\Foundation\Application;
use Luxid\Sentinel\Contracts\Guard;
use Luxid\Sentinel\Contracts\Authenticatable;
use RuntimeException;

/**
 * Authentication manager.
 *
 * Primary interface for authentication operations in your application.
 * Provides a clean API for login, logout, user retrieval, and authentication checks.
 *
 * @method \App\Entities\User|null user()
 */
class AuthManager
{
    /**
     * The default guard name.
     */
    protected string $defaultGuard = 'session';

    /**
     * The guard instances.
     *
     * @var array<string, Guard>
     */
    protected array $guards = [];

    /**
     * Create a new auth manager instance.
     *
     * @param Application $app Luxid application instance
     * @param PasswordHasher $hasher Password hasher instance
     * @param array<string, mixed> $config Auth configuration
     */
    public function __construct(
        protected Application $app,
        protected PasswordHasher $hasher,
        protected array $config = []
    ) {
        $this->defaultGuard = $config['default'] ?? 'session';
    }

    /**
     * Attempt to authenticate a user with the given credentials.
     *
     * @param array $credentials ['email' => '...', 'password' => '...']
     * @param bool $remember Remember the user (for future expansion)
     * @return bool True if authentication successful
     *
     * @example
     * ```php
     * if (auth()->attempt(['email' => $email, 'password' => $password])) {
     *     // User logged in successfully
     * }
     * ```
     */
    public function attempt(array $credentials = [], bool $remember = false): bool
    {
        return $this->guard()->attempt($credentials, $remember);
    }

    /**
     * Log a user into the application.
     *
     * @param Authenticatable $user
     * @param bool $remember
     * @return bool
     */
    public function login(Authenticatable $user, bool $remember = false): bool
    {
        return $this->guard()->login($user, $remember);
    }

    /**
     * Log the user out of the application.
     *
     * @return void
     */
    public function logout(): void
    {
        $this->guard()->logout();
    }

    /**
     * Get the currently authenticated user.
     *
     * @return Authenticatable|null
     */
    public function user(): ?Authenticatable
    {
        return $this->guard()->user();
    }

    /**
     * Get the ID of the currently authenticated user.
     *
     * @return int|string|null
     */
    public function id()
    {
        return $this->guard()->id();
    }

    /**
     * Check if the user is authenticated.
     *
     * @return bool
     */
    public function check(): bool
    {
        return $this->guard()->check();
    }

    /**
     * Check if the user is a guest (not authenticated).
     *
     * @return bool
     */
    public function guest(): bool
    {
        return $this->guard()->guest();
    }

    /**
     * Validate a user's credentials without logging them in.
     *
     * @param array $credentials
     * @return bool
     */
    public function validate(array $credentials = []): bool
    {
        return $this->guard()->validate($credentials);
    }

    /**
     * Get the guard instance.
     *
     * @param string|null $name
     * @return Guard
     */
    public function guard(?string $name = null): Guard
    {
        $name = $name ?? $this->defaultGuard;

        if (!isset($this->guards[$name])) {
            $this->guards[$name] = $this->createGuard($name);
        }

        return $this->guards[$name];
    }

    /**
     * Create a new guard instance.
     *
     * @param string $name
     * @return Guard
     */
    protected function createGuard(string $name): Guard
    {
        $config = $this->config['guards'][$name] ?? null;

        if ($config === null) {
            throw new RuntimeException("Auth guard [{$name}] is not defined.");
        }

        // Get the provider name from the guard config (defaults to 'users')
        $providerName = $config['provider'] ?? 'users';

        // Get the actual entity class from the providers config
        $provider = $this->config['providers'][$providerName]['entity'] ?? null;

        if ($provider === null) {
            throw new RuntimeException("No provider entity defined for [{$providerName}].");
        }

        // Verify the provider class exists
        if (!class_exists($provider)) {
            throw new RuntimeException("Provider class [{$provider}] does not exist.");
        }

        return match ($config['driver'] ?? 'session') {
            'session' => new SessionGuard(
                $this->app->session,
                $this->hasher,
                $provider
            ),
            default => throw new RuntimeException("Unsupported auth driver [{$config['driver']}]."),
        };
    }

    /**
     * Set the default guard.
     *
     * @param string $name
     * @return $this
     */
    public function shouldUse(string $name): self
    {
        $this->defaultGuard = $name;
        return $this;
    }

    /**
     * Get the user provider configuration.
     *
     * @param string|null $name
     * @return array|null
     */
    public function getProvider(?string $name = null): ?array
    {
        $name = $name ?? 'users';

        return $this->config['providers'][$name] ?? null;
    }

    /**
     * Dynamically call the default guard instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->guard()->$method(...$parameters);
    }
}
