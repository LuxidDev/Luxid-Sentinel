<?php

declare(strict_types=1);

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
    }

    public function boot(Application $app): void
    {
        if (isset($GLOBALS['sentinel_auth_manager'])) {
            Sentinel::setManager($GLOBALS['sentinel_auth_manager']);
        }

        $this->loadHelpers();
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

    protected function loadHelpers(): void
    {
        // helpers.php is autoloaded via composer.json
    }
}
