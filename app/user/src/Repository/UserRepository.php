<?php

declare(strict_types=1);

namespace App\User\Repository;

use App\User\Entity\User;
use Closure;
use Marko\Database\Connection\ConnectionInterface;
use Marko\Database\Entity\EntityHydrator;
use Marko\Database\Entity\EntityMetadataFactory;
use Marko\Database\Repository\Repository;

/**
 * @extends Repository<User>
 */
class UserRepository extends Repository implements UserRepositoryInterface
{
    protected const string ENTITY_CLASS = User::class;

    public function __construct(
        ConnectionInterface $connection,
        EntityMetadataFactory $metadataFactory,
        EntityHydrator $hydrator,
        ?Closure $queryBuilderFactory = null,
    ) {
        parent::__construct(
            connection: $connection,
            metadataFactory: $metadataFactory,
            hydrator: $hydrator,
            queryBuilderFactory: $queryBuilderFactory,
        );
    }

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
