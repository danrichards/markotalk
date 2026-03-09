<?php

declare(strict_types=1);

namespace App\User\Seed;

use App\Space\Entity\Space;
use App\Space\Entity\SpaceMembership;
use App\Space\Repository\SpaceMembershipRepositoryInterface;
use App\Space\Repository\SpaceRepositoryInterface;
use App\User\Entity\User;
use App\User\Enum\UserRole;
use App\User\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Marko\Config\ConfigRepositoryInterface;
use Marko\Config\Exceptions\ConfigNotFoundException;
use Marko\Database\Seed\Seeder;
use Marko\Database\Seed\SeederInterface;

/** @noinspection PhpUnused */
#[Seeder(name: 'markotalk', order: 10)]
readonly class MarkoTalkSeeder implements SeederInterface
{
    public function __construct(
        private ConfigRepositoryInterface $config,
        private SpaceRepositoryInterface $spaceRepository,
        private UserRepositoryInterface $userRepository,
        private SpaceMembershipRepositoryInterface $spaceMembershipRepository,
    ) {}

    /**
     * @throws ConfigNotFoundException
     */
    public function run(): void
    {
        $admin = $this->seedAdminUser();
        $spaces = $this->seedSpaces(createdBy: (int) $admin->id);
        $this->joinAdminToSpaces(admin: $admin, spaces: $spaces);
    }

    private function seedAdminUser(): User
    {
        $now = new DateTimeImmutable();

        $user = new User(
            id: null,
            username: 'admin',
            email: 'admin@example.com',
            password: password_hash(password: 'admin', algo: PASSWORD_BCRYPT),
            displayName: 'Admin',
            avatarUrl: null,
            role: UserRole::Admin,
            isBanned: false,
            lastSeenAt: null,
            rememberToken: null,
            createdAt: $now,
            updatedAt: $now,
        );

        $this->userRepository->save(entity: $user);

        return $user;
    }

    /**
     * @param array<Space> $spaces
     */
    private function joinAdminToSpaces(User $admin, array $spaces): void
    {
        foreach ($spaces as $space) {
            $membership = new SpaceMembership(
                id: null,
                userId: $admin->id,
                spaceId: $space->id,
                lastReadMessageId: null,
                joinedAt: new DateTimeImmutable(),
            );

            $this->spaceMembershipRepository->save(entity: $membership);
        }
    }

    /**
     * @return array<Space>
     * @throws ConfigNotFoundException
     */
    private function seedSpaces(int $createdBy): array
    {
        $defaultSpaces = $this->config->getArray(key: 'markotalk.default_spaces');
        $spaces = [];

        foreach ($defaultSpaces as $spaceData) {
            $space = new Space(
                id: null,
                name: $spaceData['name'],
                slug: $spaceData['slug'],
                description: $spaceData['description'],
                isArchived: false,
                createdBy: $createdBy,
                createdAt: new DateTimeImmutable(),
                updatedAt: new DateTimeImmutable(),
            );

            $this->spaceRepository->save(entity: $space);
            $spaces[] = $space;
        }

        return $spaces;
    }
}
