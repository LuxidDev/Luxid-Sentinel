<?php

declare(strict_types=1);

namespace Luxid\Sentinel;

use RuntimeException;

/**
 * Password hashing utility.
 *
 * Wraps PHP's native password_hash and password_verify functions
 * with consistent error handling and future-proofing for algorithm updates.
 *
 * @package Luxid\Sentinel
 */
class PasswordHasher
{
    /**
     * Default password algorithm (bcrypt).
     */
    public const DEFAULT_ALGO = PASSWORD_DEFAULT;

    /**
     * Default algorithm options.
     *
     * @var array<string, mixed>
     */
    protected array $options = [
        'cost' => 12,
    ];

    /**
     * Create a new password hasher instance.
     *
     * @param array<string, mixed> $options Hashing options
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Hash the given value.
     *
     * @param string $value Password to hash
     * @param array<string, mixed> $options Hashing options
     * @return string Hashed password
     *
     * @throws RuntimeException If hashing fails
     */
    public function hash(string $value, array $options = []): string
    {
        $options = array_merge($this->options, $options);

        $hashed = password_hash($value, self::DEFAULT_ALGO, $options);

        if ($hashed === false) {
            throw new RuntimeException('Password hashing failed. Check your PHP configuration.');
        }

        return $hashed;
    }

    /**
     * Check if the given value matches the hashed value.
     *
     * @param string $value Plain text password
     * @param string $hashedValue Hashed password
     * @return bool True if matches
     */
    public function check(string $value, string $hashedValue): bool
    {
        return password_verify($value, $hashedValue);
    }

    /**
     * Check if the given hash has been hashed using the given options.
     *
     * @param string $hashedValue Hashed password
     * @param array<string, mixed> $options Hashing options
     * @return bool True if needs rehash
     */
    public function needsRehash(string $hashedValue, array $options = []): bool
    {
        $options = array_merge($this->options, $options);

        return password_needs_rehash($hashedValue, self::DEFAULT_ALGO, $options);
    }

    /**
     * Get information about the given hash.
     *
     * @param string $hashedValue Hashed password
     * @return array<string, mixed> Hash information
     */
    public function info(string $hashedValue): array
    {
        return password_get_info($hashedValue);
    }

    /**
     * Set the default hashing options.
     *
     * @param array<string, mixed> $options
     * @return $this
     */
    public function setOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }
}
