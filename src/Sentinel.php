<?php

declare(strict_types=1);

namespace Luxid\Sentinel;

use Luxid\Contracts\Auth\AuthManager as AuthManagerContract;
use Luxid\Contracts\Auth\Guard as GuardContract;
use Luxid\Contracts\Auth\Authenticatable as AuthenticatableContract;

/**
 * Sentinel static facade.
 *
 * @package Luxid\Sentinel
 *
 * @method static bool attempt(array $credentials, bool $remember = false)
 * @method static bool login(AuthenticatableContract $user, bool $remember = false)
 * @method static void logout()
 * @method static AuthenticatableContract|null user()
 * @method static int|string|null id()
 * @method static bool check()
 * @method static bool guest()
 * @method static bool validate(array $credentials)
 * @method static GuardContract guard(?string $name = null)
 * @method static AuthManagerContract shouldUse(string $name)
 * @method static array|null getProvider(?string $name = null)
 */
class Sentinel implements AuthManagerContract
{
  protected static ?AuthManagerContract $manager = null;

  /**
   * Set the AuthManager instance.
   */
  public static function setManager(AuthManagerContract $manager): void
  {
    self::$manager = $manager;
  }

  /**
   * Get the AuthManager instance.
   */
  public static function getManager(): AuthManagerContract
  {
    if (self::$manager === null) {
      throw new \RuntimeException('Sentinel not initialized.');
    }
    return self::$manager;
  }

  /**
   * Check if Sentinel is initialized.
   */
  public static function isInitialized(): bool
  {
    return self::$manager !== null;
  }

  // AuthManagerContract implementation - delegate to manager

  public function guard(?string $name = null): GuardContract
  {
    return self::getManager()->guard($name);
  }

  public function shouldUse(string $name): self
  {
    self::getManager()->shouldUse($name);
    return $this;
  }

  public function user(): ?AuthenticatableContract
  {
    return self::getManager()->user();
  }

  public function check(): bool
  {
    return self::getManager()->check();
  }

  public function attempt(array $credentials = [], bool $remember = false): bool
  {
    return self::getManager()->attempt($credentials, $remember);
  }

  public function login(AuthenticatableContract $user, bool $remember = false): bool
  {
    return self::getManager()->login($user, $remember);
  }

  public function logout(): void
  {
    self::getManager()->logout();
  }

  public function id()
  {
    return self::getManager()->id();
  }

  public function guest(): bool
  {
    return self::getManager()->guest();
  }

  public function validate(array $credentials = []): bool
  {
    return self::getManager()->validate($credentials);
  }

  public function getProvider(?string $name = null): ?array
  {
    return self::getManager()->getProvider($name);
  }

  /**
   * Handle dynamic static method calls.
   */
  public static function __callStatic(string $method, array $arguments)
  {
    return self::getManager()->$method(...$arguments);
  }
}
