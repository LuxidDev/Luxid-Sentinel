<?php

declare(strict_types=1);

namespace Luxid\Sentinel;

/**
 * Sentinel Registry
 *
 * Provides static access to Sentinel commands and services.
 * This allows bridge files in the engine to access Sentinel classes.
 *
 * @package Luxid\Sentinel
 */
class Registry
{
    /**
     * @var array<string, callable> Registered commands
     */
    protected static array $commands = [];

    /**
     * @var array<string, mixed> Registered services
     */
    protected static array $services = [];

    /**
     * Register a command.
     *
     * @param string $name Command name (e.g., 'sentinel:install')
     * @param callable $factory Factory that returns the command instance
     * @return void
     */
    public static function registerCommand(string $name, callable $factory): void
    {
        self::$commands[$name] = $factory;
    }

    /**
     * Get a command instance.
     *
     * @param string $name Command name
     * @return object|null
     */
    public static function getCommand(string $name): ?object
    {
        if (!isset(self::$commands[$name])) {
            return null;
        }

        $factory = self::$commands[$name];
        return $factory();
    }

    /**
     * Register a service.
     *
     * @param string $name Service name
     * @param mixed $service
     * @return void
     */
    public static function register(string $name, $service): void
    {
        self::$services[$name] = $service;
    }

    /**
     * Get a service.
     *
     * @param string $name Service name
     * @return mixed|null
     */
    public static function get(string $name)
    {
        return self::$services[$name] ?? null;
    }

    /**
     * Check if a command exists.
     *
     * @param string $name Command name
     * @return bool
     */
    public static function hasCommand(string $name): bool
    {
        return isset(self::$commands[$name]);
    }

    /**
     * Get all registered command names.
     *
     * @return array
     */
    public static function getCommandNames(): array
    {
        return array_keys(self::$commands);
    }

    /**
     * Clear all registered commands and services.
     * Useful for testing.
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$commands = [];
        self::$services = [];
    }
}
