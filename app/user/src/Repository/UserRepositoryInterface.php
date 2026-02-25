<?php

declare(strict_types=1);

namespace App\User\Repository;

use App\User\Entity\User;
use Marko\Database\Repository\RepositoryInterface;

interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * Find a user by their email address.
     */
    public function findByEmail(
        string $email,
    ): ?User;

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
