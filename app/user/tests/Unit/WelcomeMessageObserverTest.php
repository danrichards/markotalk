<?php

declare(strict_types=1);

use App\Message\Entity\Message;
use App\Message\Repository\MessageRepositoryInterface;
use App\Space\Entity\Space;
use Marko\Pagination\CursorPaginator;
use App\Space\Entity\SpaceMembership;
use App\Space\Repository\SpaceMembershipRepositoryInterface;
use App\Space\Repository\SpaceRepositoryInterface;
use App\User\Entity\User;
use App\User\Enum\UserRole;
use App\User\Event\UserRegisteredEvent;
use App\User\Observer\WelcomeMessageObserver;
use Marko\Config\ConfigRepositoryInterface;
use Marko\Core\Attributes\Observer;
use Marko\Database\Entity\Entity;
use Marko\Testing\Fake\FakeConfigRepository;

function makeTestUser(): User
{
    return new User(
        id: 42,
        username: 'newuser',
        email: 'newuser@example.com',
        password: 'hashed_password',
        displayName: 'New User',
        avatarUrl: null,
        role: UserRole::User,
        isBanned: false,
        lastSeenAt: null,
        rememberToken: null,
        createdAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
        updatedAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );
}

function makeObserverTestSpace(int $id, string $name, string $slug): Space
{
    return new Space(
        id: $id,
        name: $name,
        slug: $slug,
        description: 'Test description',
        isArchived: false,
        createdBy: 1,
        createdAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
        updatedAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );
}

function makeDefaultSpacesConfig(): array
{
    return [
        ['name' => 'General', 'slug' => 'general', 'description' => 'General discussion'],
        ['name' => 'Help', 'slug' => 'help', 'description' => 'Get help with Marko'],
        ['name' => 'Showcase', 'slug' => 'showcase', 'description' => 'Show off your projects'],
    ];
}

class StubSpaceRepository implements SpaceRepositoryInterface
{
    /** @var array<string, Space> */
    public array $spaces = [];

    public function findBySlug(string $slug): ?Space
    {
        return $this->spaces[$slug] ?? null;
    }

    public function findActive(): array
    {
        return array_values(array: $this->spaces);
    }

    public function find(int $id): ?Entity { return null; }
    public function findOrFail(int $id): Entity { throw new RuntimeException(message: 'Not found'); }
    public function findAll(): array { return []; }
    public function findBy(array $criteria): array { return []; }
    public function findOneBy(array $criteria): ?Entity { return null; }
    public function save(Entity $entity): void {}
    public function delete(Entity $entity): void {}
}

class StubSpaceMembershipRepository implements SpaceMembershipRepositoryInterface
{
    /** @var array<SpaceMembership> */
    public array $savedMemberships = [];

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

    public function find(int $id): ?Entity { return null; }
    public function findOrFail(int $id): Entity { throw new RuntimeException(message: 'Not found'); }
    public function findAll(): array { return []; }
    public function findBy(array $criteria): array { return []; }
    public function findOneBy(array $criteria): ?Entity { return null; }

    public function save(Entity $entity): void
    {
        if ($entity instanceof SpaceMembership) {
            $this->savedMemberships[] = $entity;
        }
    }

    public function delete(Entity $entity): void {}
}

class StubMessageRepository implements MessageRepositoryInterface
{
    /** @var array<Message> */
    public array $savedMessages = [];

    public function findBySpace(int $spaceId, int $limit = 50): array
    {
        return [];
    }

    public function findBySpaceSince(int $spaceId, int $sinceId): array
    {
        return [];
    }

    public function findPaginated(int $spaceId, int $perPage = 50, ?string $cursor = null): CursorPaginator
    {
        return new CursorPaginator(items: [], perPage: $perPage, cursor: $cursor);
    }

    public function find(int $id): ?Entity { return null; }
    public function findOrFail(int $id): Entity { throw new RuntimeException(message: 'Not found'); }
    public function findAll(): array { return []; }
    public function findBy(array $criteria): array { return []; }
    public function findOneBy(array $criteria): ?Entity { return null; }

    public function save(Entity $entity): void
    {
        if ($entity instanceof Message) {
            $this->savedMessages[] = $entity;
        }
    }

    public function delete(Entity $entity): void {}
}

function makeObserver(
    ?StubSpaceRepository $spaceRepository = null,
    ?StubSpaceMembershipRepository $membershipRepository = null,
    ?StubMessageRepository $messageRepository = null,
    ?ConfigRepositoryInterface $config = null,
): WelcomeMessageObserver {
    $defaultConfig = new FakeConfigRepository(config: [
        'default_spaces' => makeDefaultSpacesConfig(),
    ]);

    return new WelcomeMessageObserver(
        spaceRepository: $spaceRepository ?? new StubSpaceRepository(),
        membershipRepository: $membershipRepository ?? new StubSpaceMembershipRepository(),
        messageRepository: $messageRepository ?? new StubMessageRepository(),
        config: $config ?? $defaultConfig,
    );
}

it('auto-joins new user to all default spaces', function (): void {
    $spaceRepository = new StubSpaceRepository();
    $spaceRepository->spaces['general'] = makeObserverTestSpace(id: 1, name: 'General', slug: 'general');
    $spaceRepository->spaces['help'] = makeObserverTestSpace(id: 2, name: 'Help', slug: 'help');
    $spaceRepository->spaces['showcase'] = makeObserverTestSpace(id: 3, name: 'Showcase', slug: 'showcase');

    $membershipRepository = new StubSpaceMembershipRepository();
    $user = makeTestUser();

    $observer = makeObserver(
        spaceRepository: $spaceRepository,
        membershipRepository: $membershipRepository,
    );
    $observer->handle(event: new UserRegisteredEvent(user: $user));

    expect($membershipRepository->savedMemberships)->toHaveCount(3)
        ->and($membershipRepository->savedMemberships[0]->userId)->toBe(42)
        ->and($membershipRepository->savedMemberships[0]->spaceId)->toBe(1)
        ->and($membershipRepository->savedMemberships[1]->userId)->toBe(42)
        ->and($membershipRepository->savedMemberships[1]->spaceId)->toBe(2)
        ->and($membershipRepository->savedMemberships[2]->userId)->toBe(42)
        ->and($membershipRepository->savedMemberships[2]->spaceId)->toBe(3);
});

it('posts a welcome message to the general space', function (): void {
    $spaceRepository = new StubSpaceRepository();
    $spaceRepository->spaces['general'] = makeObserverTestSpace(id: 1, name: 'General', slug: 'general');
    $spaceRepository->spaces['help'] = makeObserverTestSpace(id: 2, name: 'Help', slug: 'help');
    $spaceRepository->spaces['showcase'] = makeObserverTestSpace(id: 3, name: 'Showcase', slug: 'showcase');

    $messageRepository = new StubMessageRepository();
    $user = makeTestUser();

    $observer = makeObserver(
        spaceRepository: $spaceRepository,
        messageRepository: $messageRepository,
    );
    $observer->handle(event: new UserRegisteredEvent(user: $user));

    expect($messageRepository->savedMessages)->toHaveCount(1)
        ->and($messageRepository->savedMessages[0]->spaceId)->toBe(1)
        ->and($messageRepository->savedMessages[0]->body)->toBe('Welcome @newuser to MarkoTalk!');
});

it('uses #[Observer(event: UserRegisteredEvent::class)]', function (): void {
    $reflection = new ReflectionClass(objectOrClass: WelcomeMessageObserver::class);
    $attributes = $reflection->getAttributes(name: Observer::class);

    expect($attributes)->toHaveCount(1)
        ->and($attributes[0]->newInstance()->event)->toBe(UserRegisteredEvent::class);
});

it('reads default spaces from config/markotalk.php', function (): void {
    $spaceRepository = new StubSpaceRepository();
    $spaceRepository->spaces['custom'] = makeObserverTestSpace(id: 10, name: 'Custom', slug: 'custom');

    $membershipRepository = new StubSpaceMembershipRepository();
    $user = makeTestUser();

    $customConfig = new FakeConfigRepository(config: [
        'default_spaces' => [
            ['name' => 'Custom', 'slug' => 'custom', 'description' => 'Custom space'],
        ],
    ]);

    $observer = makeObserver(
        spaceRepository: $spaceRepository,
        membershipRepository: $membershipRepository,
        config: $customConfig,
    );
    $observer->handle(event: new UserRegisteredEvent(user: $user));

    expect($membershipRepository->savedMemberships)->toHaveCount(1)
        ->and($membershipRepository->savedMemberships[0]->spaceId)->toBe(10);
});

it('handles missing general space gracefully', function (): void {
    $spaceRepository = new StubSpaceRepository();
    // Only help and showcase exist — general is missing
    $spaceRepository->spaces['help'] = makeObserverTestSpace(id: 2, name: 'Help', slug: 'help');
    $spaceRepository->spaces['showcase'] = makeObserverTestSpace(id: 3, name: 'Showcase', slug: 'showcase');

    $membershipRepository = new StubSpaceMembershipRepository();
    $messageRepository = new StubMessageRepository();
    $user = makeTestUser();

    $observer = makeObserver(
        spaceRepository: $spaceRepository,
        membershipRepository: $membershipRepository,
        messageRepository: $messageRepository,
    );

    // Should not throw any exception
    $observer->handle(event: new UserRegisteredEvent(user: $user));

    expect($membershipRepository->savedMemberships)->toHaveCount(2)
        ->and($messageRepository->savedMessages)->toHaveCount(0);
});
