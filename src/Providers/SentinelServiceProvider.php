<?php

declare(strict_types=1);

namespace Luxid\Sentinel\Providers;

use Luxid\Foundation\Application;
use Luxid\Sentinel\AuthManager;
use Luxid\Sentinel\PasswordHasher;
use Luxid\Sentinel\Sentinel;
use Luxid\Sentinel\Registry;
use Luxid\Sentinel\Middleware\RequireAuth;
use Luxid\Sentinel\Console\InstallCommand;

/**
 * Sentinel service provider.
 *
 * Registers authentication services with Luxid.
 *
 * @package Luxid\Sentinel\Providers
 */
class SentinelServiceProvider
{
    /**
     * Register services.
     *
     * @param Application $app
     * @return void
     */
    public function register(Application $app): void
    {
        $this->registerConfig($app);
        $this->registerPasswordHasher($app);
        $this->registerAuthManager($app);
        $this->registerMiddleware($app);
        $this->registerCommands();
    }

    /**
     * Bootstrap services.
     *
     * @param Application $app
     * @return void
     */
    public function boot(Application $app): void
    {
        if (isset($GLOBALS['sentinel_auth_manager'])) {
            Sentinel::setManager($GLOBALS['sentinel_auth_manager']);
        }

        $this->loadHelpers();
    }

    /**
     * Register configuration.
     *
     * @param Application $app
     * @return void
     */
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

    /**
     * Register password hasher.
     *
     * @param Application $app
     * @return void
     */
    protected function registerPasswordHasher(Application $app): void
    {
        $hasher = new PasswordHasher();
        $GLOBALS['sentinel_hasher'] = $hasher;
        Registry::register('hasher', $hasher);
    }

    /**
     * Register auth manager.
     *
     * @param Application $app
     * @return void
     */
    protected function registerAuthManager(Application $app): void
    {
        $config = $GLOBALS['sentinel_config'] ?? [];
        $hasher = $GLOBALS['sentinel_hasher'] ?? new PasswordHasher();

        $authManager = new AuthManager($app, $hasher, $config);
        $GLOBALS['sentinel_auth_manager'] = $authManager;
        Registry::register('auth', $authManager);
    }

    /**
     * Register middleware.
     *
     * @param Application $app
     * @return void
     */
    protected function registerMiddleware(Application $app): void
    {
        $authManager = $GLOBALS['sentinel_auth_manager'] ?? null;

        if (!$authManager) {
            throw new \RuntimeException('Auth manager not initialized');
        }

        $middleware = new RequireAuth($authManager, $app->response);
        $GLOBALS['sentinel_middleware_require_auth'] = $middleware;
        Registry::register('middleware.auth', $middleware);
    }

    /**
     * Register commands with the registry.
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        // Register the install command
        Registry::registerCommand('sentinel:install', function () {
            return new InstallCommand();
        });

        // Register other commands here as needed
    }

    /**
     * Load helper functions.
     *
     * @return void
     */
    protected function loadHelpers(): void
    {
        // helpers.php is autoloaded via composer.json
    }
}
