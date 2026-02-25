<?php

declare(strict_types=1);

use App\User\Entity\User;
use App\User\Enum\UserRole;
use App\User\Middleware\PresenceMiddleware;
use App\User\Repository\UserRepositoryInterface;
use App\User\Service\DatabasePresenceTracker;
use App\User\Service\PresenceTrackerInterface;
use Marko\Database\Entity\Entity;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\Testing\Fake\FakeGuard;

function makePresenceUser(?string $lastSeenAt = null): User
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
        lastSeenAt: $lastSeenAt !== null ? new DateTimeImmutable(datetime: $lastSeenAt) : null,
        rememberToken: null,
        createdAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
        updatedAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );
}

function makePresenceTracker(mixed &$updatedUser): PresenceTrackerInterface
{
    return new class (updatedUser: $updatedUser) implements PresenceTrackerInterface {
        public function __construct(private mixed &$updatedUser) {}

        public function updateLastSeen(User $user): void
        {
            $this->updatedUser = $user;
        }

        public function isOnline(User $user): bool { return false; }

        public function getOnlineUsers(): array { return []; }
    };
}

/**
 * @param User[] $users
 */
function makeStubUserRepository(array $users = []): UserRepositoryInterface
{
    return new class (users: $users) implements UserRepositoryInterface {
        public function __construct(private array $users) {}

        public function find(int $id): ?User { return $this->users[0] ?? null; }
        public function findOrFail(int $id): Entity { return $this->users[0] ?? throw new RuntimeException(); }
        public function findAll(): array { return $this->users; }
        public function findBy(array $criteria): array { return $this->users; }
        public function findOneBy(array $criteria): ?User { return $this->users[0] ?? null; }
        public function save(Entity $entity): void {}
        public function delete(Entity $entity): void {}
        public function findByEmail(string $email): ?User { return null; }
        public function findByUsername(string $username): ?User { return null; }
        public function findByRememberToken(int $userId, string $token): ?User { return null; }
        public function updateRememberToken(User $user, ?string $token): void {}
    };
}

it('updates last_seen_at for authenticated users on each request', function (): void {
    $user = makePresenceUser();
    $guard = new FakeGuard();
    $guard->login(user: $user);

    $updatedUser = null;
    $tracker = makePresenceTracker(updatedUser: $updatedUser);

    $request = new Request(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/']);
    $middleware = new PresenceMiddleware(guard: $guard, tracker: $tracker);
    $response = $middleware->handle(
        request: $request,
        next: fn(Request $req): Response => new Response(body: 'ok', statusCode: 200),
    );

    expect($response->statusCode())->toBe(200)
        ->and($updatedUser)->toBeInstanceOf(User::class)
        ->and($updatedUser->id)->toBe(1);
});

it('skips presence update for unauthenticated requests', function (): void {
    $guard = new FakeGuard(); // no user logged in

    $updatedUser = null;
    $tracker = makePresenceTracker(updatedUser: $updatedUser);

    $request = new Request(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/']);
    $middleware = new PresenceMiddleware(guard: $guard, tracker: $tracker);
    $response = $middleware->handle(
        request: $request,
        next: fn(Request $req): Response => new Response(body: 'ok', statusCode: 200),
    );

    expect($response->statusCode())->toBe(200)
        ->and($updatedUser)->toBeNull();
});

it('considers a user online when last_seen_at is within timeout', function (): void {
    $user = makePresenceUser(lastSeenAt: date(format: 'Y-m-d H:i:s', timestamp: time() - 60)); // 60 seconds ago
    $userRepository = makeStubUserRepository(users: [$user]);

    $tracker = new DatabasePresenceTracker(
        userRepository: $userRepository,
        presenceTimeout: 300,
    );

    expect($tracker->isOnline(user: $user))->toBeTrue();
});

it('considers a user offline when last_seen_at exceeds timeout', function (): void {
    $user = makePresenceUser(lastSeenAt: date(format: 'Y-m-d H:i:s', timestamp: time() - 400)); // 400 seconds ago
    $userRepository = makeStubUserRepository(users: [$user]);

    $tracker = new DatabasePresenceTracker(
        userRepository: $userRepository,
        presenceTimeout: 300,
    );

    expect($tracker->isOnline(user: $user))->toBeFalse();
});

it('returns all online users for a given space', function (): void {
    $onlineUser = makePresenceUser(lastSeenAt: date(format: 'Y-m-d H:i:s', timestamp: time() - 60));
    $offlineUser = new User(
        id: 2,
        username: 'janesmith',
        email: 'jane@example.com',
        password: 'hashed_password',
        displayName: 'Jane Smith',
        avatarUrl: null,
        role: UserRole::User,
        isBanned: false,
        lastSeenAt: new DateTimeImmutable(datetime: date(format: 'Y-m-d H:i:s', timestamp: time() - 400)),
        rememberToken: null,
        createdAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
        updatedAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );
    $neverSeenUser = makePresenceUser();
    $userRepository = makeStubUserRepository(users: [$onlineUser, $offlineUser, $neverSeenUser]);

    $tracker = new DatabasePresenceTracker(
        userRepository: $userRepository,
        presenceTimeout: 300,
    );

    $onlineUsers = $tracker->getOnlineUsers();

    expect($onlineUsers)->toHaveCount(1)
        ->and($onlineUsers[0]->id)->toBe(1);
});
