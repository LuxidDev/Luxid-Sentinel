<?php

declare(strict_types=1);

namespace Luxid\Sentinel;

use Luxid\Sentinel\Contracts\Authenticatable;

/**
 * Sentinel static facade.
 *
 * @package Luxid\Sentinel
 *
 * @method static bool attempt(array $credentials, bool $remember = false)
 * @method static bool login(Authenticatable $user, bool $remember = false)
 * @method static void logout()
 * @method static Authenticatable|null user()
 * @method static int|string|null id()
 * @method static bool check()
 * @method static bool guest()
 * @method static bool validate(array $credentials)
 */
class Sentinel
{
    protected static ?AuthManager $manager = null;

    /**
     * Set the AuthManager instance.
     *
     * @param AuthManager $manager
     * @return void
     */
    public static function setManager(AuthManager $manager): void
    {
        self::$manager = $manager;
    }

    /**
     * Get the AuthManager instance.
     *
     * @return AuthManager
     * @throws \RuntimeException
     */
    public static function getManager(): AuthManager
    {
        if (self::$manager === null) {
            throw new \RuntimeException('Sentinel not initialized.');
        }

        return self::$manager;
    }

    /**
     * Check if Sentinel is initialized.
     *
     * @return bool
     */
    public static function isInitialized(): bool
    {
        return self::$manager !== null;
    }

    /**
     * Handle dynamic static method calls.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $method, array $arguments)
    {
        return self::getManager()->$method(...$arguments);
    }
}
