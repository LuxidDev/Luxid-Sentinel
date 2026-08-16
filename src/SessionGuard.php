<?php

declare(strict_types=1);

namespace Luxid\Haven;

use Luxid\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Luxid\Contracts\Auth\Guard as GuardContract;
use Luxid\Http\SessionInterface;
use RuntimeException;

/**
 * Session-backed authentication guard.
 *
 * Holds the authenticated user's identifier in the session and resolves the
 * entity behind it on demand. The remember token is stored in a cookie rather
 * than the session, because a value that only lives in the session cannot
 * outlive it — which is the entire point of "remember me".
 *
 * @package Luxid\Haven
 */
class SessionGuard implements GuardContract
{
    /**
     * Session key holding the authenticated user's identifier.
     */
    protected const SESSION_USER_KEY = 'haven_user_id';

    /**
     * Cookie name holding the remember token.
     */
    protected const REMEMBER_COOKIE = 'haven_remember';

    /**
     * How long a remember token stays valid, in seconds.
     */
    protected const REMEMBER_LIFETIME = 60 * 60 * 24 * 30;

    /**
     * The resolved user for this request.
     */
    protected ?AuthenticatableContract $user = null;

    /**
     * Whether the user signed out during this request.
     */
    protected bool $loggedOut = false;

    /**
     * Whether the remember cookie has already been consulted.
     */
    protected bool $recallAttempted = false;

    /**
     * @param SessionInterface       $session  Session driver
     * @param PasswordHasher         $hasher   Password hasher
     * @param class-string           $provider Entity class backing users
     */
    public function __construct(
        protected SessionInterface $session,
        protected PasswordHasher $hasher,
        protected string $provider
    ) {
    }

    /**
     * Check whether a user is signed in.
     */
    public function check(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Check whether the visitor is anonymous.
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * Get the authenticated user, resolving from the session or remember cookie.
     */
    public function user(): ?AuthenticatableContract
    {
        if ($this->loggedOut) {
            return null;
        }

        if ($this->user !== null) {
            return $this->user;
        }

        $identifier = $this->session->get(self::SESSION_USER_KEY);

        if ($identifier !== null) {
            $user = $this->retrieveUserById($identifier);

            if ($user === null) {
                // The session points at a user that no longer exists.
                $this->logout();

                return null;
            }

            return $this->user = $user;
        }

        return $this->user = $this->recallFromCookie();
    }

    /**
     * Get the authenticated user's identifier.
     */
    public function id(): mixed
    {
        return $this->user()?->getAuthIdentifier();
    }

    /**
     * Check credentials without signing anyone in.
     *
     * @param array<string, mixed> $credentials Lookup fields plus the password
     */
    public function validate(array $credentials = []): bool
    {
        $user = $this->retrieveUserByCredentials($credentials);

        return $user !== null && $this->hasValidCredentials($user, $credentials);
    }

    /**
     * Attempt to sign a user in with the given credentials.
     *
     * The password is verified even when no user matched, so the response time
     * does not reveal whether an account exists.
     *
     * @param array<string, mixed> $credentials Lookup fields plus the password
     * @param bool                 $remember    Whether to issue a remember cookie
     */
    public function attempt(array $credentials = [], bool $remember = false): bool
    {
        $user = $this->retrieveUserByCredentials($credentials);

        if ($user === null) {
            $this->hasher->fake();

            return false;
        }

        if (!$this->hasValidCredentials($user, $credentials)) {
            return false;
        }

        $this->login($user, $remember);

        return true;
    }

    /**
     * Sign a user in.
     *
     * The session id is rotated first: reusing the id a visitor arrived with
     * would let an attacker who planted that id ride the new privilege level.
     *
     * @param AuthenticatableContract $user     User to sign in
     * @param bool                    $remember Whether to issue a remember cookie
     */
    public function login(AuthenticatableContract $user, bool $remember = false): bool
    {
        $this->session->regenerate();
        $this->session->set(self::SESSION_USER_KEY, $user->getAuthIdentifier());

        if ($remember) {
            $this->issueRememberToken($user);
        }

        $this->user = $user;
        $this->loggedOut = false;

        return true;
    }

    /**
     * Sign in the user with the given identifier.
     *
     * @param int|string $id       User identifier
     * @param bool       $remember Whether to issue a remember cookie
     *
     * @return AuthenticatableContract|false The user, or false when none matched
     */
    public function loginUsingId(int|string $id, bool $remember = false): AuthenticatableContract|false
    {
        $user = $this->retrieveUserById($id);

        if ($user === null) {
            return false;
        }

        $this->login($user, $remember);

        return $user;
    }

    /**
     * Sign the current user out and rotate the session id.
     */
    public function logout(): void
    {
        $user = $this->loggedOut ? null : $this->user;

        if ($user !== null) {
            $this->clearRememberToken($user);
        }

        $this->session->remove(self::SESSION_USER_KEY);
        $this->forgetRememberCookie();
        $this->session->regenerate();

        $this->user = null;
        $this->loggedOut = true;
    }

    /**
     * Get the provider class backing this guard.
     *
     * @return class-string
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Issue a remember token and store its cookie.
     *
     * Only the token is sent to the client; it is matched against the copy on
     * the user record, so a stolen cookie can be revoked by clearing that field.
     *
     * @param AuthenticatableContract $user User to remember
     */
    protected function issueRememberToken(AuthenticatableContract $user): void
    {
        $token = bin2hex(random_bytes(32));

        $user->setRememberToken($token);
        $this->persist($user);

        $this->writeRememberCookie($user->getAuthIdentifier() . '|' . $token);
    }

    /**
     * Resolve a user from the remember cookie.
     *
     * The stored token is compared in constant time so a timing oracle cannot
     * be used to recover it byte by byte.
     */
    protected function recallFromCookie(): ?AuthenticatableContract
    {
        if ($this->recallAttempted) {
            return null;
        }

        $this->recallAttempted = true;
        $cookie = $_COOKIE[self::REMEMBER_COOKIE] ?? null;

        if (!is_string($cookie) || !str_contains($cookie, '|')) {
            return null;
        }

        [$identifier, $token] = explode('|', $cookie, 2);
        $user = $this->retrieveUserById($identifier);
        $stored = $user?->getRememberToken();

        if ($user === null || $stored === null || !hash_equals($stored, $token)) {
            $this->forgetRememberCookie();

            return null;
        }

        // A valid cookie promotes the visitor to a full session.
        $this->session->regenerate();
        $this->session->set(self::SESSION_USER_KEY, $user->getAuthIdentifier());

        return $user;
    }

    /**
     * Clear the remember token on the user record.
     *
     * @param AuthenticatableContract $user User to forget
     */
    protected function clearRememberToken(AuthenticatableContract $user): void
    {
        $user->setRememberToken(null);
        $this->persist($user);
    }

    /**
     * Write the remember cookie.
     *
     * @param string $value Cookie payload
     */
    protected function writeRememberCookie(string $value): void
    {
        if (PHP_SAPI === 'cli' || headers_sent()) {
            return;
        }

        setcookie(self::REMEMBER_COOKIE, $value, [
            'expires' => time() + self::REMEMBER_LIFETIME,
            'path' => '/',
            'secure' => $this->isSecureRequest(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * Expire the remember cookie.
     */
    protected function forgetRememberCookie(): void
    {
        unset($_COOKIE[self::REMEMBER_COOKIE]);

        if (PHP_SAPI === 'cli' || headers_sent()) {
            return;
        }

        setcookie(self::REMEMBER_COOKIE, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $this->isSecureRequest(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * Check whether the request arrived over HTTPS.
     */
    protected function isSecureRequest(): bool
    {
        return (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? null) === '443';
    }

    /**
     * Save a user entity when the provider supports it.
     *
     * @param AuthenticatableContract $user User to persist
     */
    protected function persist(AuthenticatableContract $user): void
    {
        if (method_exists($user, 'save')) {
            $user->save();
        }
    }

    /**
     * Load a user by identifier.
     *
     * @param int|string $id User identifier
     *
     * @throws RuntimeException When the provider cannot look users up by id
     */
    protected function retrieveUserById(int|string $id): ?AuthenticatableContract
    {
        $provider = $this->provider;

        if (!method_exists($provider, 'find')) {
            throw new RuntimeException(
                sprintf('Provider "%s" must implement a find() method', $provider)
            );
        }

        return $provider::find($id) ?: null;
    }

    /**
     * Load a user by their non-password credentials.
     *
     * The password field is excluded by the name the entity reports rather than
     * a hardcoded "password", so an entity using `pass` or `secret` does not end
     * up searching the database by its own plaintext password.
     *
     * @param array<string, mixed> $credentials Lookup fields plus the password
     *
     * @throws RuntimeException When the provider cannot look users up by field
     */
    protected function retrieveUserByCredentials(array $credentials): ?AuthenticatableContract
    {
        $provider = $this->provider;

        if (!method_exists($provider, 'findOne')) {
            throw new RuntimeException(
                sprintf('Provider "%s" must implement a findOne() method', $provider)
            );
        }

        $query = array_diff_key($credentials, array_flip($this->passwordFieldNames()));

        if ($query === []) {
            return null;
        }

        return $provider::findOne($query) ?: null;
    }

    /**
     * Get the credential keys that must never reach the lookup query.
     *
     * @return list<string>
     */
    protected function passwordFieldNames(): array
    {
        $names = ['password', 'password_confirmation'];
        $provider = $this->provider;

        if (method_exists($provider, 'getAuthPasswordName')) {
            $names[] = (new $provider())->getAuthPasswordName();
        }

        return array_values(array_unique($names));
    }

    /**
     * Verify the supplied password against the stored hash.
     *
     * @param AuthenticatableContract $user        User to check against
     * @param array<string, mixed>    $credentials Supplied credentials
     */
    protected function hasValidCredentials(AuthenticatableContract $user, array $credentials): bool
    {
        $field = $user->getAuthPasswordName();
        $supplied = $credentials[$field] ?? $credentials['password'] ?? null;

        if (!is_string($supplied)) {
            return false;
        }

        return $this->hasher->check($supplied, $user->getAuthPassword());
    }
}
