<?php

namespace Luxid\Haven\Providers;

use Luxid\Foundation\Application;
use Luxid\Haven\AuthManager;
use Luxid\Haven\PasswordHasher;
use Luxid\Haven\Haven;

class HavenServiceProvider
{
  public function register(Application $app): void
  {
    $this->registerConfig($app);
    $this->registerPasswordHasher($app);
    $this->registerAuthManager($app);

    // Set Haven's manager during register phase
    if (isset($GLOBALS['haven_auth_manager'])) {
      Haven::setManager($GLOBALS['haven_auth_manager']);
    }
  }

  public function boot(Application $app): void
  {
    $authManager = Haven::getManager();

    // Register the auth manager with the application
    $app->registerAuth($authManager);

    // The RouteBuilder will handle adding the appropriate middleware
    // when routes use ->auth() or ->open()
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

    $configPath = $app::$ROOT_DIR . '/config/haven.php';
    if (file_exists($configPath)) {
      $userConfig = require $configPath;
      $config = array_merge($config, $userConfig);
    }

    $GLOBALS['haven_config'] = $config;
  }

  protected function registerPasswordHasher(Application $app): void
  {
    $hasher = new PasswordHasher();
    $GLOBALS['haven_hasher'] = $hasher;
  }

  protected function registerAuthManager(Application $app): void
  {
    $config = $GLOBALS['haven_config'] ?? [];
    $hasher = $GLOBALS['haven_hasher'] ?? new PasswordHasher();

    $authManager = new AuthManager($app, $hasher, $config);
    $GLOBALS['haven_auth_manager'] = $authManager;
  }
}
