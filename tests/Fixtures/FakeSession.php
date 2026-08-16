<?php

declare(strict_types=1);

namespace Luxid\Haven\Tests\Fixtures;

use Luxid\Http\SessionInterface;

/**
 * In-memory session that records id rotations.
 *
 * Lets tests assert that the guard regenerates the session id on login without
 * needing a real PHP session, which cannot be started under the CLI SAPI.
 *
 * @package Luxid\Haven\Tests\Fixtures
 */
final class FakeSession implements SessionInterface
{
    /**
     * Stored session values.
     *
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * Stored flash messages.
     *
     * @var array<string, mixed>
     */
    private array $flash = [];

    /**
     * How many times the session id has been rotated.
     */
    public int $regenerations = 0;

    /**
     * {@inheritDoc}
     */
    public function get($key): mixed
    {
        return $this->data[$key] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function set($key, $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function remove($key): void
    {
        unset($this->data[$key]);
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * {@inheritDoc}
     */
    public function setFlash($key, $message): void
    {
        $this->flash[$key] = $message;
    }

    /**
     * {@inheritDoc}
     */
    public function getFlash($key): mixed
    {
        return $this->flash[$key] ?? false;
    }

    /**
     * {@inheritDoc}
     */
    public function hasFlash(string $key): bool
    {
        return isset($this->flash[$key]);
    }

    /**
     * {@inheritDoc}
     */
    public function regenerate(bool $deleteOldSession = true): bool
    {
        ++$this->regenerations;

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): void
    {
        $this->data = [];
    }

    /**
     * {@inheritDoc}
     */
    public function isStarted(): bool
    {
        return true;
    }
}
