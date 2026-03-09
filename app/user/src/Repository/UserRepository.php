<?php

declare(strict_types=1);

namespace App\User\Repository;

use App\User\Entity\User;
use DateTimeImmutable;
use Marko\Database\Repository\Repository;

/**
 * @extends Repository<User>
 */
class UserRepository extends Repository implements UserRepositoryInterface
{
    protected const string ENTITY_CLASS = User::class;

    /**
     * Find a user by their email address.
     */
    public function findByEmail(
        string $email,
    ): ?User {
        $result = $this->findOneBy(criteria: ['email' => $email]);

        if (!$result instanceof User) {
            return null;
        }

        return $result;
    }

    /**
     * Find a user by their username.
     */
    public function findByUsername(
        string $username,
    ): ?User {
        $result = $this->findOneBy(criteria: ['username' => $username]);

        if (!$result instanceof User) {
            return null;
        }

        return $result;
    }

    /**
     * Find a user by their remember token.
     */
    public function findByRememberToken(
        int $userId,
        string $token,
    ): ?User {
        $user = $this->find(id: $userId);

        if (!$user instanceof User) {
            return null;
        }

        if ($user->getRememberToken() !== $token) {
            return null;
        }

        return $user;
    }

    /**
     * Update the last seen timestamp for a user.
     */
    public function updateLastSeen(
        User $user,
        DateTimeImmutable $timestamp,
    ): void {
        $user->lastSeenAt = $timestamp;
        $this->connection->execute(
            'UPDATE users SET last_seen_at = ? WHERE id = ?',
            [$timestamp->format(format: 'Y-m-d H:i:s'), $user->id],
        );
    }

    /**
     * Clear the last seen timestamp for a user (mark offline).
     */
    public function clearLastSeen(
        User $user,
    ): void {
        $user->lastSeenAt = null;
        $this->connection->execute(
            'UPDATE users SET last_seen_at = NULL WHERE id = ?',
            [$user->id],
        );
    }

    /**
     * Update the remember token for a user.
     */
    public function updateRememberToken(
        User $user,
        ?string $token,
    ): void {
        $user->setRememberToken(token: $token);
        $this->save(entity: $user);
    }
}
