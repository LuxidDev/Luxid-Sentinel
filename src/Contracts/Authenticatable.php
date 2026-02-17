<?php

declare(strict_types=1);

namespace Luxid\Sentinel\Contracts;

/**
 * Contract for authenticatable entities (typically User).
 *
 * An entity that can be authenticated must implement this interface.
 * In Luxid, this is typically your User entity that extends DbEntity.
 *
 * @package Luxid\Sentinel\Contracts
 */
interface Authenticatable
{
    /**
     * Get the name of the unique identifier for the user.
     * Usually 'id' but can be customized.
     *
     * @return string
     */
    public function getAuthIdentifierName(): string;

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier();

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword(): string;

    /**
     * Get the column name where password is stored.
     * Default is 'password', but can be customized.
     *
     * @return string
     */
    public function getAuthPasswordName(): string;

    /**
     * Get the "remember me" token value.
     * Not required for basic session auth but included for future expansion.
     *
     * @return string|null
     */
    public function getRememberToken(): ?string;

    /**
     * Set the "remember me" token value.
     *
     * @param string|null $value
     * @return void
     */
    public function setRememberToken(?string $value): void;

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName(): string;
}
