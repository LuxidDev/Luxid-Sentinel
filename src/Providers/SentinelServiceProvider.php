<?php

namespace Luxid\Sentinel\Providers;

use Luxid\Foundation\Application;
use Luxid\Sentinel\AuthManager;
use Luxid\Sentinel\PasswordHasher;
use Luxid\Sentinel\Sentinel;
use Luxid\Sentinel\Middleware\RequireAuth;

class SentinelServiceProvider
{
  public function register(Application $app): void
  {
    $this->registerConfig($app);
    $this->registerPasswordHasher($app);
    $this->registerAuthManager($app);
    $this->registerMiddleware($app);

    // Set Sentinel's manager during register phase
    if (isset($GLOBALS['sentinel_auth_manager'])) {
      Sentinel::setManager($GLOBALS['sentinel_auth_manager']);
    }
  }

  public function boot(Application $app): void
  {
    $authManager = Sentinel::getManager();

    // Register the auth manager with the application
    $app->registerAuth($authManager);

    // Register the auth middleware with the router
    $middleware = new RequireAuth($authManager, $app->response);
    $app->router->addGlobalMiddleware($middleware);

    // Also register it as a named middleware for routes
    if (method_exists($app->router, 'addNamedMiddleware')) {
      $app->router->addNamedMiddleware('auth', $middleware);
    }
  }

  protected function registerConfig(Application $app): void
  {
    $config = [
      'default' => 'session',
      'guards' => [
        'session' => [
          'driver' => 'session',
          'provider' => 'users',
        ],
      ],
      'providers' => [
        'users' => [
          'entity' => 'App\\Entities\\User',
        ],
      ],
    ];

    $configPath = $app::$ROOT_DIR . '/config/sentinel.php';
    if (file_exists($configPath)) {
      $userConfig = require $configPath;
      $config = array_merge($config, $userConfig);
    }

    $GLOBALS['sentinel_config'] = $config;
  }

  protected function registerPasswordHasher(Application $app): void
  {
    $hasher = new PasswordHasher();
    $GLOBALS['sentinel_hasher'] = $hasher;
  }

  protected function registerAuthManager(Application $app): void
  {
    $config = $GLOBALS['sentinel_config'] ?? [];
    $hasher = $GLOBALS['sentinel_hasher'] ?? new PasswordHasher();

    $authManager = new AuthManager($app, $hasher, $config);
    $GLOBALS['sentinel_auth_manager'] = $authManager;
  }

  protected function registerMiddleware(Application $app): void
  {
    $authManager = $GLOBALS['sentinel_auth_manager'] ?? null;

    if (!$authManager) {
      throw new \RuntimeException('Auth manager not initialized');
    }

    $middleware = new RequireAuth($authManager, $app->response);
    $GLOBALS['sentinel_middleware_require_auth'] = $middleware;
  }
}
