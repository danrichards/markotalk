<?php

declare(strict_types=1);

use App\Message\Entity\Message;
use App\Message\Repository\MessageRepositoryInterface;
use App\Space\Controller\SpaceController;
use App\Space\Entity\Space;
use App\Space\Entity\SpaceMembership;
use App\Space\Repository\SpaceMembershipRepository;
use App\Space\Repository\SpaceMembershipRepositoryInterface;
use App\Space\Repository\SpaceRepositoryInterface;
use App\User\Entity\User;
use App\User\Repository\UserRepositoryInterface;
use App\User\Service\PresenceTrackerInterface;
use Marko\Authentication\AuthManager;
use Marko\Authentication\AuthenticatableInterface;
use Marko\Database\Connection\ConnectionInterface;
use Marko\Database\Connection\StatementInterface;
use Marko\Database\Entity\Entity;
use Marko\Database\Entity\EntityHydrator;
use Marko\Database\Entity\EntityMetadataFactory;
use Marko\Pagination\CursorPaginator;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\Security\Contracts\CsrfTokenManagerInterface;
use Marko\View\ViewInterface;

// ─── Helper factories ────────────────────────────────────────────────────────

/**
 * @param array<array<string, mixed>> $queryResult
 * @param array<array{sql: string, bindings: array<mixed>}>|null $queryHistory
 */
function createUnreadMockConnectionWithHistory(
    array $queryResult = [],
    ?array &$queryHistory = null,
): ConnectionInterface {
    $queryHistory ??= [];

    return new class ($queryResult, $queryHistory) implements ConnectionInterface
    {
        /**
         * @param array<array<string, mixed>> $queryResult
         * @param array<array{sql: string, bindings: array<mixed>}> $queryHistory
         */
        public function __construct(
            private array $queryResult,
            private array &$queryHistory,
        ) {}

        public function connect(): void {}

        public function disconnect(): void {}

        public function isConnected(): bool
        {
            return true;
        }

        /**
         * @param array<mixed> $bindings
         * @return array<array<string, mixed>>
         */
        public function query(
            string $sql,
            array $bindings = [],
        ): array {
            $this->queryHistory[] = ['sql' => $sql, 'bindings' => $bindings];

            return $this->queryResult;
        }

        /**
         * @param array<mixed> $bindings
         */
        public function execute(
            string $sql,
            array $bindings = [],
        ): int {
            $this->queryHistory[] = ['sql' => $sql, 'bindings' => $bindings];

            return 1;
        }

        public function prepare(
            string $sql,
        ): StatementInterface {
            throw new RuntimeException(message: 'Not implemented');
        }

        public function lastInsertId(): int
        {
            return 1;
        }
    };
}

function createUnreadRepository(
    array $queryResult = [],
    ?array &$queryHistory = null,
): SpaceMembershipRepository {
    $connection = createUnreadMockConnectionWithHistory(queryResult: $queryResult, queryHistory: $queryHistory);
    $metadataFactory = new EntityMetadataFactory();
    $hydrator = new EntityHydrator();

    return new SpaceMembershipRepository(
        connection: $connection,
        metadataFactory: $metadataFactory,
        hydrator: $hydrator,
    );
}

// ─── Tests ───────────────────────────────────────────────────────────────────

it('returns zero when user has read all messages', function (): void {
    $membershipRow = [
        'id' => 1,
        'user_id' => 1,
        'space_id' => 2,
        'last_read_message_id' => 20,
        'joined_at' => '2026-02-24 00:00:00',
    ];

    $queryHistory = [];
    $metadataFactory = new EntityMetadataFactory();
    $hydrator = new EntityHydrator();

    // First call returns membership, second returns zero count
    $connection = new class ($membershipRow, 0, $queryHistory) implements ConnectionInterface {
        private int $callCount = 0;

        /**
         * @param array<string, mixed> $membershipRow
         * @param array<array{sql: string, bindings: array<mixed>}> $queryHistory
         */
        public function __construct(
            private readonly array $membershipRow,
            private readonly int $unreadCount,
            private array &$queryHistory,
        ) {}

        public function connect(): void {}

        public function disconnect(): void {}

        public function isConnected(): bool { return true; }

        /**
         * @param array<mixed> $bindings
         * @return array<array<string, mixed>>
         */
        public function query(string $sql, array $bindings = []): array
        {
            $this->queryHistory[] = ['sql' => $sql, 'bindings' => $bindings];
            $this->callCount++;

            if ($this->callCount === 1) {
                return [$this->membershipRow];
            }

            return [['count' => $this->unreadCount]];
        }

        /** @param array<mixed> $bindings */
        public function execute(string $sql, array $bindings = []): int
        {
            $this->queryHistory[] = ['sql' => $sql, 'bindings' => $bindings];

            return 1;
        }

        public function prepare(string $sql): StatementInterface
        {
            throw new RuntimeException(message: 'Not implemented');
        }

        public function lastInsertId(): int { return 1; }
    };

    $repository = new SpaceMembershipRepository(
        connection: $connection,
        metadataFactory: $metadataFactory,
        hydrator: $hydrator,
    );

    $count = $repository->countUnread(userId: 1, spaceId: 2);

    expect($count)->toBe(0);
});

it('calculates unread count for a user in a space', function (): void {
    $membershipRow = [
        'id' => 1,
        'user_id' => 1,
        'space_id' => 2,
        'last_read_message_id' => 10,
        'joined_at' => '2026-02-24 00:00:00',
    ];

    $queryHistory = [];
    $connection = createUnreadMockConnectionWithHistory(queryResult: [], queryHistory: $queryHistory);
    $metadataFactory = new EntityMetadataFactory();
    $hydrator = new EntityHydrator();

    // First call returns membership row, second returns count
    $callCount = 0;
    $connection = new class ($membershipRow, 5, $queryHistory) implements ConnectionInterface {
        private int $callCount = 0;

        /**
         * @param array<string, mixed> $membershipRow
         * @param array<array{sql: string, bindings: array<mixed>}> $queryHistory
         */
        public function __construct(
            private readonly array $membershipRow,
            private readonly int $unreadCount,
            private array &$queryHistory,
        ) {}

        public function connect(): void {}

        public function disconnect(): void {}

        public function isConnected(): bool { return true; }

        /**
         * @param array<mixed> $bindings
         * @return array<array<string, mixed>>
         */
        public function query(string $sql, array $bindings = []): array
        {
            $this->queryHistory[] = ['sql' => $sql, 'bindings' => $bindings];
            $this->callCount++;

            if ($this->callCount === 1) {
                return [$this->membershipRow];
            }

            return [['count' => $this->unreadCount]];
        }

        /** @param array<mixed> $bindings */
        public function execute(string $sql, array $bindings = []): int
        {
            $this->queryHistory[] = ['sql' => $sql, 'bindings' => $bindings];

            return 1;
        }

        public function prepare(string $sql): StatementInterface
        {
            throw new RuntimeException(message: 'Not implemented');
        }

        public function lastInsertId(): int { return 1; }
    };

    $repository = new SpaceMembershipRepository(
        connection: $connection,
        metadataFactory: $metadataFactory,
        hydrator: $hydrator,
    );

    $count = $repository->countUnread(userId: 1, spaceId: 2);

    expect($count)->toBe(5);
});

// ─── Controller helpers ───────────────────────────────────────────────────────

function makeUnreadControllerView(): ViewInterface
{
    return new class implements ViewInterface {
        public string $lastTemplate = '';

        /** @var array<string, mixed> */
        public array $lastData = [];

        public function render(string $template, array $data = []): Response
        {
            $this->lastTemplate = $template;
            $this->lastData = $data;

            return new Response(body: '<html>space</html>', statusCode: 200);
        }

        public function renderToString(string $template, array $data = []): string
        {
            return '<html>space</html>';
        }
    };
}

/**
 * @param array<Message> $messages
 */
function makeUnreadMessageRepositoryStub(array $messages = []): MessageRepositoryInterface
{
    return new class ($messages) implements MessageRepositoryInterface {
        /** @param array<Message> $messages */
        public function __construct(private readonly array $messages) {}

        public function findBySpace(int $spaceId, int $limit = 50): array
        {
            return $this->messages;
        }

        public function findBySpaceSince(int $spaceId, int $sinceId): array { return []; }

        public function findEditedSince(int $spaceId, \DateTimeImmutable $since): array { return []; }

        public function findPaginated(int $spaceId, int $perPage = 50, ?string $cursor = null): CursorPaginator
        {
            return new CursorPaginator(items: [], perPage: $perPage);
        }

        public function find(int $id): ?Entity { return null; }

        public function findOrFail(int $id): Entity
        {
            throw new RuntimeException(message: 'Not implemented');
        }

        public function findAll(): array { return []; }

        public function findBy(array $criteria): array { return []; }

        public function findOneBy(array $criteria): ?Entity { return null; }

        public function save(Entity $entity): void {}

        public function delete(Entity $entity): void {}
    };
}

/**
 * @param array<Message> $messages
 */
function makeUnreadMembershipRepositoryStub(
    ?SpaceMembership $byUserAndSpace = null,
    array $allForUser = [],
): SpaceMembershipRepositoryInterface {
    return new class ($byUserAndSpace, $allForUser) implements SpaceMembershipRepositoryInterface {
        public bool $saveCalled = false;

        public ?int $lastReadMessageIdUpdated = null;

        public function __construct(
            private readonly ?SpaceMembership $byUserAndSpace,
            private readonly array $allForUser,
        ) {}

        public function findByUserAndSpace(int $userId, int $spaceId): ?SpaceMembership
        {
            return $this->byUserAndSpace;
        }

        public function findAllForUser(int $userId): array { return $this->allForUser; }

        public function findAllForSpace(int $spaceId): array { return []; }

        public function countUnread(int $userId, int $spaceId): int { return 0; }

        public function updateLastReadMessageId(SpaceMembership $membership, int $messageId): void
        {
            $this->lastReadMessageIdUpdated = $messageId;
        }

        public function find(int $id): ?Entity { return null; }

        public function findOrFail(int $id): Entity
        {
            throw new RuntimeException(message: 'Not implemented');
        }

        public function findAll(): array { return []; }

        public function findBy(array $criteria): array { return []; }

        public function findOneBy(array $criteria): ?Entity { return null; }

        public function clearLastReadMessageId(int $messageId): void {}

        public function save(Entity $entity): void
        {
            $this->saveCalled = true;
        }

        public function delete(Entity $entity): void {}
    };
}

function makeUnreadAuthUser(int $id = 1): AuthenticatableInterface
{
    return new class ($id) implements AuthenticatableInterface {
        public function __construct(private readonly int $id) {}

        public function getAuthIdentifier(): int|string { return $this->id; }

        public function getAuthIdentifierName(): string { return 'id'; }

        public function getAuthPassword(): string { return 'hashed'; }

        public function getRememberToken(): ?string { return null; }

        public function setRememberToken(?string $token): void {}

        public function getRememberTokenName(): string { return 'remember_token'; }
    };
}

function makeUnreadAuthManager(?AuthenticatableInterface $user = null): AuthManager
{
    return new class ($user) extends AuthManager {
        public function __construct(
            private readonly ?AuthenticatableInterface $mockUser,
        ) {
            // Skip parent constructor
        }

        public function user(): ?AuthenticatableInterface
        {
            return $this->mockUser;
        }
    };
}

function makeUnreadSpaceRepositoryStub(?Space $bySlug = null): SpaceRepositoryInterface
{
    return new class ($bySlug) implements SpaceRepositoryInterface {
        public function __construct(private readonly ?Space $bySlug) {}

        public function findBySlug(string $slug): ?Space { return $this->bySlug; }

        public function findActive(): array { return []; }

        public function find(int $id): ?Entity { return null; }

        public function findOrFail(int $id): Entity
        {
            throw new RuntimeException(message: 'Not implemented');
        }

        public function findAll(): array { return []; }

        public function findBy(array $criteria): array { return []; }

        public function findOneBy(array $criteria): ?Entity { return null; }

        public function save(Entity $entity): void {}

        public function delete(Entity $entity): void {}
    };
}

function makeUnreadSpace(int $id = 1, string $slug = 'general'): Space
{
    return new Space(
        id: $id,
        name: 'General',
        slug: $slug,
        description: null,
        isArchived: false,
        createdBy: 1,
        createdAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
        updatedAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );
}

function makeUnreadMembership(int $spaceId = 1): SpaceMembership
{
    return new SpaceMembership(
        id: 1,
        userId: 1,
        spaceId: $spaceId,
        lastReadMessageId: null,
        joinedAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );
}

function makeUnreadMessage(int $id = 42, int $spaceId = 1): Message
{
    return new Message(
        id: $id,
        spaceId: $spaceId,
        userId: 1,
        body: 'Hello',
        bodyHtml: '<p>Hello</p>',
        isPinned: false,
        editedAt: null,
        createdAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );
}

it('updates last_read_message_id when viewing a space', function (): void {
    $user = makeUnreadAuthUser();
    $space = makeUnreadSpace();
    $membership = makeUnreadMembership();
    $message = makeUnreadMessage();

    $spaces = makeUnreadSpaceRepositoryStub(bySlug: $space);
    $memberships = makeUnreadMembershipRepositoryStub(byUserAndSpace: $membership);
    $messages = makeUnreadMessageRepositoryStub(messages: [$message]);
    $view = makeUnreadControllerView();
    $auth = makeUnreadAuthManager(user: $user);

    $userRepository = new class implements UserRepositoryInterface {
        public function findByEmail(string $email): ?User { return null; }

        public function findByUsername(string $username): ?User { return null; }

        public function findByRememberToken(int $userId, string $token): ?User { return null; }

        public function updateRememberToken(User $user, ?string $token): void {}

        public function find(int $id): ?Entity { return null; }

        public function findOrFail(int $id): Entity
        {
            throw new RuntimeException(message: 'Not implemented');
        }

        public function findAll(): array { return []; }

        public function findBy(array $criteria): array { return []; }

        public function findOneBy(array $criteria): ?Entity { return null; }

        public function save(Entity $entity): void {}

        public function delete(Entity $entity): void {}
    };

    $csrf = new class implements CsrfTokenManagerInterface {
        public function get(): string { return 'test-token'; }

        public function validate(string $token): bool { return true; }

        public function regenerate(): string { return 'test-token'; }
    };

    $presence = new class implements PresenceTrackerInterface {
        public function updateLastSeen(User $user): void {}

        public function isOnline(User $user): bool { return false; }

        public function getOnlineUsers(): array { return []; }
    };

    $controller = new SpaceController(
        spaces: $spaces,
        memberships: $memberships,
        messages: $messages,
        users: $userRepository,
        view: $view,
        auth: $auth,
        csrf: $csrf,
        presence: $presence,
    );

    $controller->show(slug: 'general', request: new Request(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/spaces/general']));

    expect($memberships->lastReadMessageIdUpdated)->toBe(42);
});

it('displays unread count badge next to space name in sidebar', function (): void {
    $template = file_get_contents('/Users/markshust/Sites/markotalk/app/space/resources/views/space/show.latte');

    expect($template)
        ->toContain('$unreadCounts[$navSpace->id]')
        ->and($template)->toContain('class="space-item-unread"');
});

it('hides badge when unread count is zero', function (): void {
    $template = file_get_contents('/Users/markshust/Sites/markotalk/app/space/resources/views/space/show.latte');

    expect($template)
        ->toContain('$unreadCounts[$navSpace->id] > 0');
});
