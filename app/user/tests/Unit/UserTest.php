<?php

declare(strict_types=1);

use App\User\Entity\User;
use App\User\Enum\UserRole;
use Marko\Authentication\AuthenticatableInterface;
use Marko\Authorization\AuthorizableInterface;
use Marko\Notification\Contracts\NotifiableInterface;

function makeUser(): User
{
    return new User(
        id: 1,
        username: 'johndoe',
        email: 'john@example.com',
        password: 'hashed_password',
        displayName: 'John Doe',
        avatarUrl: null,
        role: UserRole::User,
        isBanned: false,
        lastSeenAt: null,
        rememberToken: null,
        createdAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
        updatedAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );
}

it('creates a User entity with all required fields', function (): void {
    $user = makeUser();

    expect($user->id)->toBe(1)
        ->and($user->username)->toBe('johndoe')
        ->and($user->email)->toBe('john@example.com')
        ->and($user->password)->toBe('hashed_password')
        ->and($user->displayName)->toBe('John Doe')
        ->and($user->avatarUrl)->toBeNull()
        ->and($user->role)->toBe(UserRole::User)
        ->and($user->isBanned)->toBeFalse()
        ->and($user->lastSeenAt)->toBeNull()
        ->and($user->rememberToken)->toBeNull()
        ->and($user->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($user->updatedAt)->toBeInstanceOf(DateTimeImmutable::class);
});

it('implements AuthenticatableInterface with correct methods', function (): void {
    $user = makeUser();

    expect($user)->toBeInstanceOf(AuthenticatableInterface::class)
        ->and($user->getAuthIdentifier())->toBe(1)
        ->and($user->getAuthIdentifierName())->toBe('id')
        ->and($user->getAuthPassword())->toBe('hashed_password')
        ->and($user->getRememberToken())->toBeNull()
        ->and($user->getRememberTokenName())->toBe('remember_token');

    $user->setRememberToken(token: 'some_token');
    expect($user->getRememberToken())->toBe('some_token');
});

it('implements NotifiableInterface with correct methods', function (): void {
    $user = makeUser();

    expect($user)->toBeInstanceOf(NotifiableInterface::class)
        ->and($user->getNotifiableId())->toBe(1)
        ->and($user->getNotifiableType())->toBe(User::class)
        ->and($user->routeNotificationFor(channel: 'mail'))->toBe('john@example.com')
        ->and($user->routeNotificationFor(channel: 'database'))->toBe([
            'type' => User::class,
            'id' => 1,
        ]);
});

it('uses UserRole backed enum for the role field', function (): void {
    $userRole = makeUser();
    $adminRole = new User(
        id: 2,
        username: 'adminuser',
        email: 'admin@example.com',
        password: 'hashed_password',
        displayName: 'Admin User',
        avatarUrl: null,
        role: UserRole::Admin,
        isBanned: false,
        lastSeenAt: null,
        rememberToken: null,
        createdAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
        updatedAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );

    expect($userRole->role)->toBe(UserRole::User)
        ->and($userRole->role->value)->toBe('user')
        ->and($adminRole->role)->toBe(UserRole::Admin)
        ->and($adminRole->role->value)->toBe('admin')
        ->and(UserRole::from(value: 'user'))->toBe(UserRole::User)
        ->and(UserRole::from(value: 'admin'))->toBe(UserRole::Admin);
});

it('has a migration that creates the users table with all columns and indexes', function (): void {
    $migrationPath = __DIR__ . '/../../../../database/migrations/20260225000000_create_users.php';

    expect(file_exists(filename: $migrationPath))->toBeTrue();

    $migration = require $migrationPath;

    expect($migration)->toBeInstanceOf(Marko\Database\Migration\Migration::class);

    $sql = '';
    $connectionMock = new class ($sql) implements \Marko\Database\Connection\ConnectionInterface {
        public function __construct(private string &$capturedSql) {}

        public function connect(): void {}

        public function disconnect(): void {}

        public function isConnected(): bool
        {
            return true;
        }

        public function query(string $sql, array $bindings = []): array
        {
            return [];
        }

        public function execute(string $sql, array $bindings = []): int
        {
            $this->capturedSql .= $sql;
            return 0;
        }

        public function prepare(string $sql): \Marko\Database\Connection\StatementInterface
        {
            throw new \RuntimeException(message: 'Not implemented');
        }

        public function lastInsertId(): int
        {
            return 0;
        }
    };

    $migration->up(connection: $connectionMock);

    expect($sql)->toContain('CREATE TABLE')
        ->and($sql)->toContain('users')
        ->and($sql)->toContain('id')
        ->and($sql)->toContain('username')
        ->and($sql)->toContain('email')
        ->and($sql)->toContain('password')
        ->and($sql)->toContain('display_name')
        ->and($sql)->toContain('avatar_url')
        ->and($sql)->toContain('role')
        ->and($sql)->toContain('is_banned')
        ->and($sql)->toContain('last_seen_at')
        ->and($sql)->toContain('remember_token')
        ->and($sql)->toContain('created_at')
        ->and($sql)->toContain('updated_at')
        ->and($sql)->toContain('UNIQUE');
});

it('stores password as hashed string and role as enum value', function (): void {
    $hashedPassword = password_hash(password: 'secret', algo: PASSWORD_BCRYPT);
    $user = new User(
        id: 1,
        username: 'johndoe',
        email: 'john@example.com',
        password: $hashedPassword,
        displayName: 'John Doe',
        avatarUrl: null,
        role: UserRole::Admin,
        isBanned: false,
        lastSeenAt: null,
        rememberToken: null,
        createdAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
        updatedAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );

    expect($user->password)->toBeString()
        ->and(password_verify(password: 'secret', hash: $user->password))->toBeTrue()
        ->and($user->password)->not->toBe('secret')
        ->and($user->role)->toBeInstanceOf(UserRole::class)
        ->and($user->role->value)->toBeString()
        ->and($user->role->value)->toBe('admin');
});
