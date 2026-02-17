<?php

declare(strict_types=1);

namespace Luxid\Sentinel;

use Luxid\Http\SessionInterface;
use Luxid\Sentinel\Contracts\Guard;
use Luxid\Sentinel\Contracts\Authenticatable;
use RuntimeException;

/**
 * Session-based authentication guard.
 *
 * Manages user authentication state using PHP sessions.
 * Stores user ID in session and retrieves the full user entity on each request.
 *
 * @package Luxid\Sentinel
 */
class SessionGuard implements Guard
{
    /**
     * Session key for storing authenticated user ID.
     */
    protected const SESSION_USER_KEY = 'sentinel_user_id';

    /**
     * Session key for storing "remember me" token.
     */
    protected const SESSION_REMEMBER_KEY = 'sentinel_remember_token';

    /**
     * The currently authenticated user.
     */
    protected ?Authenticatable $user = null;

    /**
     * Whether the user was retrieved from the session on this request.
     */
    protected bool $loggedOut = false;

    /**
     * Create a new session guard instance.
     *
     * @param SessionInterface $session Luxid session instance
     * @param PasswordHasher $hasher Password hasher instance
     * @param string $provider Entity provider class name
     */
    public function __construct(
        protected SessionInterface $session,
        protected PasswordHasher $hasher,
        protected string $provider
    ) {}

    /**
     * {@inheritdoc}
     */
    public function check(): bool
    {
        return $this->user() !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * {@inheritdoc}
     */
    public function user(): ?Authenticatable
    {
        if ($this->loggedOut) {
            return null;
        }

        // Return cached user if available
        if ($this->user !== null) {
            return $this->user;
        }

        $userId = $this->session->get(self::SESSION_USER_KEY);

        if ($userId === null) {
            return null;
        }

        // Retrieve user from database using the provider
        $user = $this->retrieveUserById($userId);

        if ($user === null) {
            // User not found in database - clear session
            $this->logout();
            return null;
        }

        $this->user = $user;

        return $this->user;
    }

    /**
     * {@inheritdoc}
     */
    public function id()
    {
        $user = $this->user();

        return $user ? $user->getAuthIdentifier() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(array $credentials = []): bool
    {
        $user = $this->retrieveUserByCredentials($credentials);

        if ($user === null) {
            return false;
        }

        return $this->hasValidCredentials($user, $credentials);
    }

    /**
     * {@inheritdoc}
     */
    public function attempt(array $credentials = [], bool $remember = false): bool
    {
        $user = $this->retrieveUserByCredentials($credentials);

        if ($user === null) {
            return false;
        }

        if (!$this->hasValidCredentials($user, $credentials)) {
            return false;
        }

        $this->login($user, $remember);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function login(Authenticatable $user, bool $remember = false): bool
    {
        $this->updateSession($user->getAuthIdentifier());

        if ($remember) {
            $this->setRememberToken($user);
        }

        $this->user = $user;
        $this->loggedOut = false;

        return true;
    }

    /**
     * Log the user in using their ID.
     *
     * @param mixed $id User ID
     * @param bool $remember
     * @return Authenticatable|false
     */
    public function loginUsingId($id, bool $remember = false)
    {
        $user = $this->retrieveUserById($id);

        if ($user === null) {
            return false;
        }

        $this->login($user, $remember);

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function logout(): void
    {
        $user = $this->user();

        if ($user !== null) {
            $this->clearRememberToken($user);
        }

        $this->session->remove(self::SESSION_USER_KEY);
        $this->session->remove(self::SESSION_REMEMBER_KEY);

        $this->user = null;
        $this->loggedOut = true;
    }

    /**
     * {@inheritdoc}
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * Update the session with the user ID.
     *
     * @param mixed $id
     * @return void
     */
    protected function updateSession($id): void
    {
        $this->session->set(self::SESSION_USER_KEY, $id);
    }

    /**
     * Set the "remember me" token for the user.
     *
     * @param Authenticatable $user
     * @return void
     */
    protected function setRememberToken(Authenticatable $user): void
    {
        $token = bin2hex(random_bytes(32));

        $user->setRememberToken($token);

        // Save token to database
        if (method_exists($user, 'save')) {
            $user->save();
        }

        $this->session->set(self::SESSION_REMEMBER_KEY, $token);
    }

    /**
     * Clear the "remember me" token for the user.
     *
     * @param Authenticatable $user
     * @return void
     */
    protected function clearRememberToken(Authenticatable $user): void
    {
        $user->setRememberToken(null);

        if (method_exists($user, 'save')) {
            $user->save();
        }
    }

    /**
     * Retrieve a user by their ID.
     *
     * @param mixed $id
     * @return Authenticatable|null
     */
    protected function retrieveUserById($id): ?Authenticatable
    {
        $provider = $this->provider;

        if (!method_exists($provider, 'find')) {
            throw new RuntimeException(
                sprintf('Provider "%s" must implement a find() method', $provider)
            );
        }

        return $provider::find($id);
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param array $credentials
     * @return Authenticatable|null
     */
    protected function retrieveUserByCredentials(array $credentials): ?Authenticatable
    {
        $provider = $this->provider;

        // Remove password from credentials for lookup
        $query = array_diff_key($credentials, array_flip(['password']));

        if (empty($query)) {
            return null;
        }

        if (!method_exists($provider, 'findOne')) {
            throw new RuntimeException(
                sprintf('Provider "%s" must implement a findOne() method', $provider)
            );
        }

        return $provider::findOne($query);
    }

    /**
     * Check if the user has valid credentials.
     *
     * @param Authenticatable $user
     * @param array $credentials
     * @return bool
     */
    protected function hasValidCredentials(Authenticatable $user, array $credentials): bool
    {
        $passwordName = $user->getAuthPasswordName();

        if (!isset($credentials[$passwordName])) {
            return false;
        }

        return $this->hasher->check(
            $credentials[$passwordName],
            $user->getAuthPassword()
        );
    }
}
