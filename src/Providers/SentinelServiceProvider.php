<?php

declare(strict_types=1);

namespace Luxid\Sentinel\Providers;

use Luxid\Foundation\Application;
use Luxid\Sentinel\AuthManager;
use Luxid\Sentinel\PasswordHasher;
use Luxid\Sentinel\Sentinel;
use Luxid\Sentinel\Middleware\RequireAuth;
use Luxid\Sentinel\Console\InstallCommand;

/**
 * Sentinel service provider.
 *
 * Registers authentication services with the Luxid container.
 * Binds AuthManager, PasswordHasher, and registers middleware.
 * Also registers the install command with Juice CLI.
 *
 * @package Luxid\Sentinel\Providers
 */
class SentinelServiceProvider
{
    /**
     * Register services with the container.
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
        $this->registerCommands($app);
    }

    /**
     * Bootstrap services after registration.
     *
     * @param Application $app
     * @return void
     */
    public function boot(Application $app): void
    {
        // Set the AuthManager instance in Sentinel helper
        if ($app->has('auth')) {
            Sentinel::setManager($app->get('auth'));
        }

        // Load helper functions if not already loaded
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
        // Default configuration
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

        // Allow config file to override defaults
        $configPath = $app::$ROOT_DIR . '/config/sentinel.php';
        if (file_exists($configPath)) {
            $userConfig = require $configPath;
            $config = array_merge($config, $userConfig);
        }

        // Store config in application container
        $app->set('sentinel.config', $config);
    }

    /**
     * Register password hasher.
     *
     * @param Application $app
     * @return void
     */
    protected function registerPasswordHasher(Application $app): void
    {
        $app->set('sentinel.hasher', function () {
            return new PasswordHasher();
        });
    }

    /**
     * Register auth manager.
     *
     * @param Application $app
     * @return void
     */
    protected function registerAuthManager(Application $app): void
    {
        $app->set('auth', function () use ($app) {
            $config = $app->get('sentinel.config');
            $hasher = $app->get('sentinel.hasher');

            return new AuthManager($app, $hasher, $config);
        });

        // Also register as singleton
        $app->set(AuthManager::class, function () use ($app) {
            return $app->get('auth');
        });
    }

    /**
     * Register middleware.
     *
     * @param Application $app
     * @return void
     */
    protected function registerMiddleware(Application $app): void
    {
        // Register middleware alias for use in routes
        $app->set('sentinel.middleware.require-auth', function () use ($app) {
            return new RequireAuth(
                $app->get('auth'),
                $app->response
            );
        });

        // Add middleware alias to router
        if (method_exists($app->router, 'aliasMiddleware')) {
            $app->router->aliasMiddleware('auth', 'sentinel.middleware.require-auth');
        }
    }

    /**
     * Register console commands.
     *
     * @param Application $app
     * @return void
     */
    protected function registerCommands(Application $app): void
    {
        // Only register in CLI mode
        if (php_sapi_name() !== 'cli') {
            return;
        }

        // Register install command with Juice
        $app->set('sentinel.command.install', function () {
            return new InstallCommand();
        });

        // Add to Juice commands if available
        if (isset($app->console) && method_exists($app->console, 'add')) {
            $app->console->add('sentinel:install', 'sentinel.command.install');
        }
    }

    /**
     * Load helper functions.
     *
     * @return void
     */
    protected function loadHelpers(): void
    {
        // helpers.php is autoloaded via composer.json files array
        // This ensures the auth() helper is always available
    }
}
