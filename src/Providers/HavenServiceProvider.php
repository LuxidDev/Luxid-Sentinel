<?php

declare(strict_types=1);

namespace Luxid\Haven\Providers;

use Luxid\Foundation\Application;
use Luxid\Haven\AuthManager;
use Luxid\Haven\Haven;
use Luxid\Haven\PasswordHasher;

/**
 * Wires Haven into the application.
 *
 * Discovered automatically through `extra.luxid.providers` in composer.json.
 * The built services are held on the provider rather than in `$GLOBALS`, which
 * previously made them reachable — and overwritable — from anywhere in the
 * process.
 *
 * @package Luxid\Haven\Providers
 */
class HavenServiceProvider
{
    /**
     * Default guard and provider configuration.
     *
     * @var array<string, mixed>
     */
    private const DEFAULT_CONFIG = [
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

    /**
     * Resolved configuration for this process.
     *
     * @var array<string, mixed>
     */
    private static array $config = [];

    /**
     * Shared password hasher.
     */
    private static ?PasswordHasher $hasher = null;

    /**
     * Shared auth manager.
     */
    private static ?AuthManager $manager = null;

    /**
     * Build Haven's services.
     *
     * @param Application $app The booting application
     */
    public function register(Application $app): void
    {
        self::$config = $this->loadConfig($app);
        self::$hasher ??= new PasswordHasher($this->hasherOptions());
        self::$manager = new AuthManager($app, self::$hasher, self::$config);

        Haven::setManager(self::$manager);
    }

    /**
     * Bind the auth manager onto the application.
     *
     * @param Application $app The booting application
     */
    public function boot(Application $app): void
    {
        $app->registerAuth(self::$manager ?? Haven::getManager());
    }

    /**
     * Merge the application's `config/haven.php` over the defaults.
     *
     * Merging is recursive so an application that overrides one guard does not
     * silently drop the provider list.
     *
     * @param Application $app The booting application
     *
     * @return array<string, mixed>
     */
    protected function loadConfig(Application $app): array
    {
        $path = $app::$ROOT_DIR . '/config/haven.php';

        if (!is_file($path)) {
            return self::DEFAULT_CONFIG;
        }

        $config = require $path;

        return is_array($config)
            ? array_replace_recursive(self::DEFAULT_CONFIG, $config)
            : self::DEFAULT_CONFIG;
    }

    /**
     * Get the hashing options declared in configuration.
     *
     * @return array<string, mixed>
     */
    protected function hasherOptions(): array
    {
        return self::$config['hashing'] ?? [];
    }

    /**
     * Get the resolved configuration.
     *
     * @return array<string, mixed>
     */
    public static function config(): array
    {
        return self::$config;
    }

    /**
     * Get the shared password hasher.
     */
    public static function hasher(): PasswordHasher
    {
        return self::$hasher ??= new PasswordHasher();
    }

    /**
     * Discard the built services, so tests can boot a clean provider.
     */
    public static function reset(): void
    {
        self::$config = [];
        self::$hasher = null;
        self::$manager = null;
    }
}
