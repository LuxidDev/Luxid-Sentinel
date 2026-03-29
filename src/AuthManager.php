<?php

declare(strict_types=1);

namespace Luxid\Sentinel;

use Luxid\Foundation\Application;
use Luxid\Contracts\Auth\AuthManager as AuthManagerContract;
use Luxid\Contracts\Auth\Guard as GuardContract;
use Luxid\Contracts\Auth\Authenticatable as AuthenticatableContract;
use RuntimeException;

class AuthManager implements AuthManagerContract
{
  protected string $defaultGuard = 'session';
  protected array $guards = [];

  public function __construct(
    protected Application $app,
    protected PasswordHasher $hasher,
    protected array $config = []
  ) {
    $this->defaultGuard = $config['default'] ?? 'session';
  }

  public function attempt(array $credentials = [], bool $remember = false): bool
  {
    return $this->guard()->attempt($credentials, $remember);
  }

  public function login(AuthenticatableContract $user, bool $remember = false): bool
  {
    return $this->guard()->login($user, $remember);
  }

  public function logout(): void
  {
    $this->guard()->logout();
  }

  public function user(): ?AuthenticatableContract
  {
    return $this->guard()->user();
  }

  public function id()
  {
    return $this->guard()->id();
  }

  public function check(): bool
  {
    return $this->guard()->check();
  }

  public function guest(): bool
  {
    return $this->guard()->guest();
  }

  public function validate(array $credentials = []): bool
  {
    return $this->guard()->validate($credentials);
  }

  public function guard(?string $name = null): GuardContract
  {
    $name = $name ?? $this->defaultGuard;

    if (!isset($this->guards[$name])) {
      $this->guards[$name] = $this->createGuard($name);
    }

    return $this->guards[$name];
  }

  public function shouldUse(string $name): self
  {
    $this->defaultGuard = $name;
    return $this;
  }

  protected function createGuard(string $name): GuardContract
  {
    $config = $this->config['guards'][$name] ?? null;

    if ($config === null) {
      throw new RuntimeException("Auth guard [{$name}] is not defined.");
    }

    $providerName = $config['provider'] ?? 'users';
    $provider = $this->config['providers'][$providerName]['entity'] ?? null;

    if ($provider === null) {
      throw new RuntimeException("No provider entity defined for [{$providerName}].");
    }

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

  public function getProvider(?string $name = null): ?array
  {
    $name = $name ?? 'users';
    return $this->config['providers'][$name] ?? null;
  }

  public function __call(string $method, array $parameters)
  {
    return $this->guard()->$method(...$parameters);
  }
}
