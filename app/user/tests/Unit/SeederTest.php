<?php

declare(strict_types=1);

use App\Space\Entity\Space;
use App\Space\Entity\SpaceMembership;
use App\Space\Repository\SpaceMembershipRepositoryInterface;
use App\Space\Repository\SpaceRepositoryInterface;
use App\User\Entity\User;
use App\User\Enum\UserRole;
use App\User\Repository\UserRepositoryInterface;
use App\User\Seed\MarkoTalkSeeder;
use Marko\Config\ConfigRepositoryInterface;
use Marko\Database\Entity\Entity;
use Marko\Database\Seed\Seeder;

function makeConfigRepository(array $defaultSpaces): ConfigRepositoryInterface
{
    return new class (defaultSpaces: $defaultSpaces) implements ConfigRepositoryInterface {
        public function __construct(
            private array $defaultSpaces,
        ) {}

        public function get(string $key, ?string $scope = null): mixed
        {
            return match ($key) {
                'default_spaces', 'markotalk.default_spaces' => $this->defaultSpaces,
                default => null,
            };
        }

        public function has(string $key, ?string $scope = null): bool
        {
            return true;
        }

        public function getString(string $key, ?string $scope = null): string
        {
            return '';
        }

        public function getInt(string $key, ?string $scope = null): int
        {
            return 0;
        }

        public function getBool(string $key, ?string $scope = null): bool
        {
            return false;
        }

        public function getFloat(string $key, ?string $scope = null): float
        {
            return 0.0;
        }

        public function getArray(string $key, ?string $scope = null): array
        {
            return match ($key) {
                'default_spaces', 'markotalk.default_spaces' => $this->defaultSpaces,
                default => [],
            };
        }

        public function all(?string $scope = null): array
        {
            return [];
        }

        public function withScope(string $scope): ConfigRepositoryInterface
        {
            return $this;
        }
    };
}

function makeSpaceRepositoryMock(): SpaceRepositoryInterface
{
    return new class () implements SpaceRepositoryInterface {
        /** @var array<Space> */
        public array $savedSpaces = [];

        public function find(int $id): ?Entity
        {
            return null;
        }

        public function findOrFail(int $id): Entity
        {
            throw new RuntimeException(message: 'Not implemented');
        }

        public function findAll(): array
        {
            return $this->savedSpaces;
        }

        public function findBy(array $criteria): array
        {
            return [];
        }

        public function findOneBy(array $criteria): ?Entity
        {
            return null;
        }

        public function existsBy(array $criteria): bool
        {
            return $this->findOneBy(criteria: $criteria) !== null;
        }

        public function save(Entity $entity): void
        {
            if ($entity instanceof Space) {
                $entity->id = count(value: $this->savedSpaces) + 1;
                $this->savedSpaces[] = $entity;
            }
        }

        public function delete(Entity $entity): void {}

        public function findBySlug(string $slug): ?Space
        {
            return null;
        }

        public function findActive(): array
        {
            return $this->savedSpaces;
        }
    };
}

function makeUserRepositoryMock(): UserRepositoryInterface
{
    return new class () implements UserRepositoryInterface {
        /** @var array<User> */
        public array $savedUsers = [];

        public function find(int $id): ?Entity
        {
            return null;
        }

        public function findOrFail(int $id): Entity
        {
            throw new RuntimeException(message: 'Not implemented');
        }

        public function findAll(): array
        {
            return $this->savedUsers;
        }

        public function findBy(array $criteria): array
        {
            return [];
        }

        public function findOneBy(array $criteria): ?Entity
        {
            return null;
        }

        public function existsBy(array $criteria): bool
        {
            return $this->findOneBy(criteria: $criteria) !== null;
        }

        public function save(Entity $entity): void
        {
            if ($entity instanceof User) {
                $entity->id = count(value: $this->savedUsers) + 1;
                $this->savedUsers[] = $entity;
            }
        }

        public function delete(Entity $entity): void {}

        public function findByEmail(string $email): ?User
        {
            return null;
        }

        public function findByUsername(string $username): ?User
        {
            return null;
        }

        public function findByRememberToken(int $userId, string $token): ?User
        {
            return null;
        }

        public function updateRememberToken(User $user, ?string $token): void {}

        public function updateLastSeen(User $user, DateTimeImmutable $timestamp): void {}

        public function clearLastSeen(User $user): void {}
    };
}

function makeSpaceMembershipRepositoryMock(): SpaceMembershipRepositoryInterface
{
    return new class () implements SpaceMembershipRepositoryInterface {
        /** @var array<SpaceMembership> */
        public array $savedMemberships = [];

        public function find(int $id): ?Entity
        {
            return null;
        }

        public function findOrFail(int $id): Entity
        {
            throw new RuntimeException(message: 'Not implemented');
        }

        public function findAll(): array
        {
            return $this->savedMemberships;
        }

        public function findBy(array $criteria): array
        {
            return [];
        }

        public function findOneBy(array $criteria): ?Entity
        {
            return null;
        }

        public function existsBy(array $criteria): bool
        {
            return $this->findOneBy(criteria: $criteria) !== null;
        }

        public function clearLastReadMessageId(int $messageId): void {}

        public function save(Entity $entity): void
        {
            if ($entity instanceof SpaceMembership) {
                $entity->id = count(value: $this->savedMemberships) + 1;
                $this->savedMemberships[] = $entity;
            }
        }

        public function delete(Entity $entity): void {}

        public function findByUserAndSpace(int $userId, int $spaceId): ?SpaceMembership
        {
            return null;
        }

        public function findAllForUser(int $userId): array
        {
            return [];
        }

        public function findAllForSpace(int $spaceId): array
        {
            return [];
        }

        public function countUnread(int $userId, int $spaceId): int { return 0; }

        public function updateLastReadMessageId(SpaceMembership $membership, int $messageId): void {}
    };
}

$defaultSpaces = [
    ['name' => 'General', 'slug' => 'general', 'description' => 'General discussion'],
    ['name' => 'Help', 'slug' => 'help', 'description' => 'Get help with Marko'],
    ['name' => 'Showcase', 'slug' => 'showcase', 'description' => 'Show off your projects'],
];

it('creates default spaces from config', function () use ($defaultSpaces): void {
    $spaceRepository = makeSpaceRepositoryMock();
    $userRepository = makeUserRepositoryMock();
    $membershipRepository = makeSpaceMembershipRepositoryMock();
    $config = makeConfigRepository(defaultSpaces: $defaultSpaces);

    $seeder = new MarkoTalkSeeder(
        config: $config,
        spaceRepository: $spaceRepository,
        userRepository: $userRepository,
        spaceMembershipRepository: $membershipRepository,
    );
    $seeder->run();

    expect($spaceRepository->savedSpaces)->toHaveCount(3)
        ->and($spaceRepository->savedSpaces[0]->slug)->toBe('general')
        ->and($spaceRepository->savedSpaces[1]->slug)->toBe('help')
        ->and($spaceRepository->savedSpaces[2]->slug)->toBe('showcase');
});

it('creates an admin user with admin role', function () use ($defaultSpaces): void {
    $spaceRepository = makeSpaceRepositoryMock();
    $userRepository = makeUserRepositoryMock();
    $membershipRepository = makeSpaceMembershipRepositoryMock();
    $config = makeConfigRepository(defaultSpaces: $defaultSpaces);

    $seeder = new MarkoTalkSeeder(
        config: $config,
        spaceRepository: $spaceRepository,
        userRepository: $userRepository,
        spaceMembershipRepository: $membershipRepository,
    );
    $seeder->run();

    expect($userRepository->savedUsers)->toHaveCount(1)
        ->and($userRepository->savedUsers[0]->username)->toBe('admin')
        ->and($userRepository->savedUsers[0]->role)->toBe(UserRole::Admin);
});

it('hashes the admin password', function () use ($defaultSpaces): void {
    $spaceRepository = makeSpaceRepositoryMock();
    $userRepository = makeUserRepositoryMock();
    $membershipRepository = makeSpaceMembershipRepositoryMock();
    $config = makeConfigRepository(defaultSpaces: $defaultSpaces);

    $seeder = new MarkoTalkSeeder(
        config: $config,
        spaceRepository: $spaceRepository,
        userRepository: $userRepository,
        spaceMembershipRepository: $membershipRepository,
    );
    $seeder->run();

    $admin = $userRepository->savedUsers[0];

    expect($admin->password)->not->toBe('admin')
        ->and(password_verify(password: 'admin', hash: $admin->password))->toBeTrue();
});

it('joins admin user to all default spaces', function () use ($defaultSpaces): void {
    $spaceRepository = makeSpaceRepositoryMock();
    $userRepository = makeUserRepositoryMock();
    $membershipRepository = makeSpaceMembershipRepositoryMock();
    $config = makeConfigRepository(defaultSpaces: $defaultSpaces);

    $seeder = new MarkoTalkSeeder(
        config: $config,
        spaceRepository: $spaceRepository,
        userRepository: $userRepository,
        spaceMembershipRepository: $membershipRepository,
    );
    $seeder->run();

    $admin = $userRepository->savedUsers[0];
    $memberships = $membershipRepository->savedMemberships;

    expect($memberships)->toHaveCount(3)
        ->and($memberships[0]->userId)->toBe($admin->id)
        ->and($memberships[0]->spaceId)->toBe($spaceRepository->savedSpaces[0]->id)
        ->and($memberships[1]->userId)->toBe($admin->id)
        ->and($memberships[1]->spaceId)->toBe($spaceRepository->savedSpaces[1]->id)
        ->and($memberships[2]->userId)->toBe($admin->id)
        ->and($memberships[2]->spaceId)->toBe($spaceRepository->savedSpaces[2]->id);
});

it('uses #[Seeder] attribute with correct name and order', function (): void {
    $reflection = new ReflectionClass(objectOrClass: MarkoTalkSeeder::class);
    $attributes = $reflection->getAttributes(name: Seeder::class);

    expect($attributes)->toHaveCount(1)
        ->and($attributes[0]->newInstance()->name)->toBe('markotalk')
        ->and($attributes[0]->newInstance()->order)->toBe(10);
});
