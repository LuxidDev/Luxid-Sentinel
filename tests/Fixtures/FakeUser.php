<?php

declare(strict_types=1);

namespace Luxid\Haven\Tests\Fixtures;

use Luxid\Contracts\Auth\Authenticatable;

/**
 * In-memory user provider and entity.
 *
 * Doubles as the provider class the guard is configured with: the static
 * `find()` and `findOne()` methods read from a table that tests populate, and
 * `$lookups` records the queries the guard issued so tests can assert the
 * password never reached the lookup.
 *
 * @package Luxid\Haven\Tests\Fixtures
 */
final class FakeUser implements Authenticatable
{
    /**
     * Users available to the provider, keyed by identifier.
     *
     * @var array<int|string, self>
     */
    public static array $table = [];

    /**
     * Lookup criteria the guard passed to findOne().
     *
     * @var list<array<string, mixed>>
     */
    public static array $lookups = [];

    /**
     * Name of the password field on this entity.
     */
    public static string $passwordField = 'password';

    /**
     * How many times save() was called.
     */
    public int $saves = 0;

    /**
     * @param int|string  $id            User identifier
     * @param string      $email         Email address
     * @param string      $password      Hashed password
     * @param string|null $rememberToken Current remember token
     */
    public function __construct(
        public int|string $id = 1,
        public string $email = 'jhay@luxid.dev',
        public string $password = '',
        public ?string $rememberToken = null,
    ) {
    }

    /**
     * Reset the provider between tests.
     */
    public static function reset(): void
    {
        self::$table = [];
        self::$lookups = [];
        self::$passwordField = 'password';
    }

    /**
     * Register a user with the provider.
     */
    public static function add(self $user): self
    {
        self::$table[$user->id] = $user;

        return $user;
    }

    /**
     * Look a user up by identifier.
     *
     * @param int|string $id User identifier
     */
    public static function find(int|string $id): ?self
    {
        return self::$table[$id] ?? null;
    }

    /**
     * Look a user up by arbitrary criteria.
     *
     * @param array<string, mixed> $criteria Column/value pairs
     */
    public static function findOne(array $criteria): ?self
    {
        self::$lookups[] = $criteria;

        foreach (self::$table as $user) {
            foreach ($criteria as $field => $value) {
                if (!property_exists($user, $field) || $user->{$field} !== $value) {
                    continue 2;
                }
            }

            return $user;
        }

        return null;
    }

    /**
     * Record that the entity was persisted.
     */
    public function save(): bool
    {
        ++$this->saves;

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthIdentifier(): int|string
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthPassword(): string
    {
        return $this->password;
    }

    /**
     * {@inheritDoc}
     */
    public function getAuthPasswordName(): string
    {
        return self::$passwordField;
    }

    /**
     * {@inheritDoc}
     */
    public function getRememberToken(): ?string
    {
        return $this->rememberToken;
    }

    /**
     * {@inheritDoc}
     */
    public function setRememberToken(?string $value): void
    {
        $this->rememberToken = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getRememberTokenName(): string
    {
        return 'rememberToken';
    }
}
