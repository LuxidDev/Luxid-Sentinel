<?php

declare(strict_types=1);

namespace Luxid\Sentinel;

use Luxid\Http\SessionInterface;
use Luxid\Contracts\Auth\Guard as GuardContract;
use Luxid\Contracts\Auth\Authenticatable as AuthenticatableContract;
use RuntimeException;

class SessionGuard implements GuardContract
{
  protected const SESSION_USER_KEY = 'sentinel_user_id';
  protected const SESSION_REMEMBER_KEY = 'sentinel_remember_token';

  protected ?AuthenticatableContract $user = null;
  protected bool $loggedOut = false;

  public function __construct(
    protected SessionInterface $session,
    protected PasswordHasher $hasher,
    protected string $provider
  ) {}

  public function check(): bool
  {
    return $this->user() !== null;
  }

  public function guest(): bool
  {
    return !$this->check();
  }

  public function user(): ?AuthenticatableContract
  {
    if ($this->loggedOut) {
      return null;
    }

    if ($this->user !== null) {
      return $this->user;
    }

    $userId = $this->session->get(self::SESSION_USER_KEY);

    if ($userId === null) {
      return null;
    }

    $user = $this->retrieveUserById($userId);

    if ($user === null) {
      $this->logout();
      return null;
    }

    $this->user = $user;
    return $this->user;
  }

  public function id()
  {
    $user = $this->user();
    return $user ? $user->getAuthIdentifier() : null;
  }

  public function validate(array $credentials = []): bool
  {
    $user = $this->retrieveUserByCredentials($credentials);

    if ($user === null) {
      return false;
    }

    return $this->hasValidCredentials($user, $credentials);
  }

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

  public function login(AuthenticatableContract $user, bool $remember = false): bool
  {
    error_log("=== SessionGuard::login ===");
    error_log("User ID: " . $user->getAuthIdentifier());
    error_log("Session status before: " . session_status());

    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    $this->updateSession($user->getAuthIdentifier());

    error_log("Session after update: " . json_encode($_SESSION));

    if ($remember) {
      $this->setRememberToken($user);
    }

    $this->user = $user;
    $this->loggedOut = false;

    error_log("Login successful, session data: " . json_encode($_SESSION));

    return true;
  }

  public function loginUsingId($id, bool $remember = false)
  {
    $user = $this->retrieveUserById($id);

    if ($user === null) {
      return false;
    }

    $this->login($user, $remember);
    return $user;
  }

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

  public function getProvider()
  {
    return $this->provider;
  }

  protected function updateSession($id): void
  {
    $this->session->set(self::SESSION_USER_KEY, $id);
  }

  protected function setRememberToken(AuthenticatableContract $user): void
  {
    $token = bin2hex(random_bytes(32));
    $user->setRememberToken($token);

    if (method_exists($user, 'save')) {
      $user->save();
    }

    $this->session->set(self::SESSION_REMEMBER_KEY, $token);
  }

  protected function clearRememberToken(AuthenticatableContract $user): void
  {
    $user->setRememberToken(null);

    if (method_exists($user, 'save')) {
      $user->save();
    }
  }

  protected function retrieveUserById($id): ?AuthenticatableContract
  {
    $provider = $this->provider;

    if (!method_exists($provider, 'find')) {
      throw new RuntimeException(
        sprintf('Provider "%s" must implement a find() method', $provider)
      );
    }

    return $provider::find($id);
  }

  protected function retrieveUserByCredentials(array $credentials): ?AuthenticatableContract
  {
    $provider = $this->provider;
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

  protected function hasValidCredentials(AuthenticatableContract $user, array $credentials): bool
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
