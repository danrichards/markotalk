<?php

declare(strict_types=1);

namespace App\User\Repository;

use App\User\Entity\User;
use DateTimeImmutable;
use Marko\Database\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<User>
 */
interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * Find a user by their email address.
     */
    public function findByEmail(
        string $email,
    ): ?User;

    /**
     * Update the last seen timestamp for a user.
     */
    public function updateLastSeen(
        User $user,
        DateTimeImmutable $timestamp,
    ): void;

    /**
     * Clear the last seen timestamp for a user (mark offline).
     */
    public function clearLastSeen(
        User $user,
    ): void;

    /**
     * Find a user by their username.
     */
    public function findByUsername(
        string $username,
    ): ?User;

    /**
     * Find a user by their remember token.
     */
    public function findByRememberToken(
        int $userId,
        string $token,
    ): ?User;

    /**
     * Update the remember token for a user.
     */
    public function updateRememberToken(
        User $user,
        ?string $token,
    ): void;
}
