<?php

declare(strict_types=1);

use App\User\Entity\User;
use App\User\Enum\UserRole;
use App\User\Repository\UserRepository;
use App\User\Repository\UserRepositoryInterface;
use Marko\Authentication\Contracts\PasswordHasherInterface;
use Marko\Authentication\Contracts\UserProviderInterface;
use Marko\Database\Connection\ConnectionInterface;
use Marko\Database\Connection\StatementInterface;
use Marko\Database\Entity\EntityHydrator;
use Marko\Database\Entity\EntityMetadataFactory;

function makeUserRow(): array
{
    return [
        'id' => 1,
        'username' => 'johndoe',
        'email' => 'john@example.com',
        'password' => '$2y$10$hashed_password',
        'display_name' => 'John Doe',
        'avatar_url' => null,
        'role' => 'user',
        'is_banned' => 0,
        'last_seen_at' => null,
        'remember_token' => null,
        'created_at' => '2026-02-24 00:00:00',
        'updated_at' => '2026-02-24 00:00:00',
    ];
}

function makeUserRepositoryConnection(array $queryResult = []): ConnectionInterface
{
    return new readonly class ($queryResult) implements ConnectionInterface {
        public function __construct(
            private array $queryResult,
        ) {}

        public function connect(): void {}

        public function disconnect(): void {}

        public function isConnected(): bool
        {
            return true;
        }

        public function query(string $sql, array $bindings = []): array
        {
            return $this->queryResult;
        }

        public function execute(string $sql, array $bindings = []): int
        {
            return 1;
        }

        public function prepare(string $sql): StatementInterface
        {
            throw new RuntimeException(message: 'Not implemented');
        }

        public function lastInsertId(): int
        {
            return 1;
        }
    };
}

function makeUserRepository(array $queryResult = []): UserRepository
{
    return new UserRepository(
        connection: makeUserRepositoryConnection(queryResult: $queryResult),
        metadataFactory: new EntityMetadataFactory(),
        hydrator: new EntityHydrator(),
    );
}

it('finds a user by id', function (): void {
    $repository = makeUserRepository(queryResult: [makeUserRow()]);

    $user = $repository->find(id: 1);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->id)->toBe(1)
        ->and($user->username)->toBe('johndoe')
        ->and($user->email)->toBe('john@example.com');
});

it('finds a user by email for credential lookup', function (): void {
    $repository = makeUserRepository(queryResult: [makeUserRow()]);

    $user = $repository->findByEmail(email: 'john@example.com');

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->email)->toBe('john@example.com');
});

it('returns null when no user found by email', function (): void {
    $repository = makeUserRepository(queryResult: []);

    $user = $repository->findByEmail(email: 'notfound@example.com');

    expect($user)->toBeNull();
});

it('finds a user by username', function (): void {
    $repository = makeUserRepository(queryResult: [makeUserRow()]);

    $user = $repository->findByUsername(username: 'johndoe');

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->username)->toBe('johndoe');
});

it('validates credentials against hashed password', function (): void {
    $userRepository = new class () implements UserRepositoryInterface {
        public function find(int $id): ?\Marko\Database\Entity\Entity { return null; }
        public function findOrFail(int $id): \Marko\Database\Entity\Entity { throw new \RuntimeException(message: 'Not found'); }
        public function findAll(): array { return []; }
        public function findBy(array $criteria): array { return []; }
        public function findOneBy(array $criteria): ?\Marko\Database\Entity\Entity { return null; }
        public function existsBy(array $criteria): bool { return $this->findOneBy(criteria: $criteria) !== null; }
        public function save(\Marko\Database\Entity\Entity $entity): void {}
        public function delete(\Marko\Database\Entity\Entity $entity): void {}
        public function findByEmail(string $email): ?User { return null; }
        public function findByUsername(string $username): ?User { return null; }
        public function findByRememberToken(int $userId, string $token): ?User { return null; }
        public function updateRememberToken(User $user, ?string $token): void {}
        public function updateLastSeen(User $user, \DateTimeImmutable $timestamp): void {}
        public function clearLastSeen(User $user): void {}
    };

    $verifiedValue = null;
    $verifiedHash = null;
    $hasher = new class ($verifiedValue, $verifiedHash) implements PasswordHasherInterface {
        public function __construct(
            private mixed &$verifiedValue,
            private mixed &$verifiedHash,
        ) {}

        public function hash(string $password): string { return $password; }

        public function verify(string $password, string $hash): bool
        {
            $this->verifiedValue = $password;
            $this->verifiedHash = $hash;
            return true;
        }

        public function needsRehash(string $hash): bool { return false; }
    };

    $user = new User(
        id: 1,
        username: 'johndoe',
        email: 'john@example.com',
        password: '$2y$10$hashedpassword',
        displayName: 'John Doe',
        avatarUrl: null,
        role: UserRole::User,
        isBanned: false,
        lastSeenAt: null,
        rememberToken: null,
        createdAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
        updatedAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );

    $provider = new \App\User\Provider\DatabaseUserProvider(
        userRepository: $userRepository,
        hasher: $hasher,
    );

    $result = $provider->validateCredentials(
        user: $user,
        credentials: ['password' => 'secret'],
    );

    expect($result)->toBeTrue()
        ->and($verifiedValue)->toBe('secret')
        ->and($verifiedHash)->toBe('$2y$10$hashedpassword');
});

it('retrieves user by remember token', function (): void {
    $userRow = array_merge(makeUserRow(), ['remember_token' => 'valid-token-abc']);
    $repository = makeUserRepository(queryResult: [$userRow]);

    $user = $repository->findByRememberToken(userId: 1, token: 'valid-token-abc');

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->id)->toBe(1)
        ->and($user->rememberToken)->toBe('valid-token-abc');
});

it('returns null when remember token does not match', function (): void {
    $userRow = array_merge(makeUserRow(), ['remember_token' => 'valid-token-abc']);
    $repository = makeUserRepository(queryResult: [$userRow]);

    $user = $repository->findByRememberToken(userId: 1, token: 'wrong-token');

    expect($user)->toBeNull();
});

it('updates remember token for a user', function (): void {
    $executedSql = [];
    $connection = new class ($executedSql) implements ConnectionInterface {
        private bool $firstQuery = true;

        public function __construct(private array &$executedSql) {}

        public function connect(): void {}

        public function disconnect(): void {}

        public function isConnected(): bool { return true; }

        public function query(string $sql, array $bindings = []): array
        {
            if ($this->firstQuery) {
                $this->firstQuery = false;
                return [makeUserRow()];
            }
            return [];
        }

        public function execute(string $sql, array $bindings = []): int
        {
            $this->executedSql[] = $sql;
            return 1;
        }

        public function prepare(string $sql): StatementInterface
        {
            throw new RuntimeException(message: 'Not implemented');
        }

        public function lastInsertId(): int { return 1; }
    };

    $repository = new UserRepository(
        connection: $connection,
        metadataFactory: new EntityMetadataFactory(),
        hydrator: new EntityHydrator(),
    );

    $user = $repository->find(id: 1);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->rememberToken)->toBeNull();

    $repository->updateRememberToken(user: $user, token: 'new-remember-token');

    expect($user->rememberToken)->toBe('new-remember-token')
        ->and($executedSql)->not->toBeEmpty();
});

it('updates last seen timestamp using save instead of raw SQL', function (): void {
    $executedSql = [];
    $connection = new class ($executedSql) implements ConnectionInterface {
        private bool $firstQuery = true;

        public function __construct(private array &$executedSql) {}

        public function connect(): void {}

        public function disconnect(): void {}

        public function isConnected(): bool { return true; }

        public function query(string $sql, array $bindings = []): array
        {
            if ($this->firstQuery) {
                $this->firstQuery = false;
                return [makeUserRow()];
            }
            return [];
        }

        public function execute(string $sql, array $bindings = []): int
        {
            $this->executedSql[] = $sql;
            return 1;
        }

        public function prepare(string $sql): StatementInterface
        {
            throw new RuntimeException(message: 'Not implemented');
        }

        public function lastInsertId(): int { return 1; }
    };

    $repository = new UserRepository(
        connection: $connection,
        metadataFactory: new EntityMetadataFactory(),
        hydrator: new EntityHydrator(),
    );

    $user = $repository->find(id: 1);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->lastSeenAt)->toBeNull();

    $timestamp = new DateTimeImmutable(datetime: '2026-03-14 12:00:00');
    $repository->updateLastSeen(user: $user, timestamp: $timestamp);

    expect($user->lastSeenAt)->toBe($timestamp)
        ->and($executedSql)->not->toBeEmpty();
});

it('clears last seen timestamp using save instead of raw SQL', function (): void {
    $executedSql = [];
    $connection = new class ($executedSql) implements ConnectionInterface {
        private bool $firstQuery = true;

        public function __construct(private array &$executedSql) {}

        public function connect(): void {}

        public function disconnect(): void {}

        public function isConnected(): bool { return true; }

        public function query(string $sql, array $bindings = []): array
        {
            if ($this->firstQuery) {
                $this->firstQuery = false;
                return [array_merge(makeUserRow(), ['last_seen_at' => '2026-03-14 12:00:00'])];
            }
            return [];
        }

        public function execute(string $sql, array $bindings = []): int
        {
            $this->executedSql[] = $sql;
            return 1;
        }

        public function prepare(string $sql): StatementInterface
        {
            throw new RuntimeException(message: 'Not implemented');
        }

        public function lastInsertId(): int { return 1; }
    };

    $repository = new UserRepository(
        connection: $connection,
        metadataFactory: new EntityMetadataFactory(),
        hydrator: new EntityHydrator(),
    );

    $user = $repository->find(id: 1);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->lastSeenAt)->toBeInstanceOf(DateTimeImmutable::class);

    $repository->clearLastSeen(user: $user);

    expect($user->lastSeenAt)->toBeNull()
        ->and($executedSql)->not->toBeEmpty()
        ->and($executedSql[0])->toContain('last_seen_at = NULL');
});

it('has module.php with correct interface-to-implementation bindings', function (): void {
    $modulePath = __DIR__ . '/../../module.php';

    expect(file_exists(filename: $modulePath))->toBeTrue();

    $module = require $modulePath;

    expect($module)->toBeArray()
        ->and($module)->toHaveKey('bindings')
        ->and($module['bindings'])->toHaveKey(UserRepositoryInterface::class)
        ->and($module['bindings'])->toHaveKey(UserProviderInterface::class);
});
