<?php

declare(strict_types=1);

namespace App\User\Provider;

use App\User\Entity\User;
use App\User\Repository\UserRepositoryInterface;
use Marko\Authentication\AuthenticatableInterface;
use Marko\Authentication\Contracts\PasswordHasherInterface;
use Marko\Authentication\Contracts\UserProviderInterface;

readonly class DatabaseUserProvider implements UserProviderInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $hasher,
    ) {}

    /**
     * Retrieve a user by their unique identifier.
     */
    public function retrieveById(
        int|string $identifier,
    ): ?AuthenticatableInterface {
        return $this->userRepository->find(id: (int) $identifier);
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param array<string, mixed> $credentials
     */
    public function retrieveByCredentials(
        array $credentials,
    ): ?AuthenticatableInterface {
        $email = $credentials['email'] ?? null;

        if ($email === null) {
            return null;
        }

        return $this->userRepository->findByEmail(email: $email);
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param array<string, mixed> $credentials
     */
    public function validateCredentials(
        AuthenticatableInterface $user,
        array $credentials,
    ): bool {
        $password = $credentials['password'] ?? '';

        return $this->hasher->verify(
            password: $password,
            hash: $user->getAuthPassword(),
        );
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     */
    public function retrieveByRememberToken(
        int|string $identifier,
        string $token,
    ): ?AuthenticatableInterface {
        return $this->userRepository->findByRememberToken(
            userId: (int) $identifier,
            token: $token,
        );
    }

    /**
     * Update the "remember me" token for the given user in storage.
     */
    public function updateRememberToken(
        AuthenticatableInterface $user,
        ?string $token,
    ): void {
        if (!$user instanceof User) {
            return;
        }

        $this->userRepository->updateRememberToken(
            user: $user,
            token: $token,
        );
    }
}
