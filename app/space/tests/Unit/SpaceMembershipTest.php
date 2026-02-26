<?php

declare(strict_types=1);

use App\Space\Entity\SpaceMembership;
use App\Space\Repository\SpaceMembershipRepository;
use App\Space\Repository\SpaceMembershipRepositoryInterface;
use Marko\Database\Connection\ConnectionInterface;
use Marko\Database\Connection\StatementInterface;
use Marko\Database\Entity\EntityHydrator;
use Marko\Database\Entity\EntityMetadataFactory;

it('creates a SpaceMembership entity with all required fields', function (): void {
    $membership = new SpaceMembership(
        id: null,
        userId: 1,
        spaceId: 2,
        lastReadMessageId: null,
        joinedAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );

    expect($membership->id)->toBeNull()
        ->and($membership->userId)->toBe(1)
        ->and($membership->spaceId)->toBe(2)
        ->and($membership->lastReadMessageId)->toBeNull()
        ->and($membership->joinedAt)->toBeInstanceOf(DateTimeImmutable::class);
});

it('has a migration with UNIQUE constraint on user_id and space_id', function (): void {
    $migrationFile = dirname(path: __DIR__, levels: 4) . '/database/migrations/20260225191743_create_space_memberships.php';

    $content = file_get_contents($migrationFile);

    expect(file_exists($migrationFile))->toBeTrue()
        ->and($content)
        ->toContain('space_memberships')
        ->toContain('user_id')
        ->toContain('space_id')
        ->toContain('last_read_message_id')
        ->toContain('joined_at')
        ->toContain('PRIMARY KEY')
        ->toContain('UNIQUE')
        ->toContain('DROP TABLE');
});

// Helper functions

function createSpaceMembershipMockConnection(
    array $queryResult = [],
): ConnectionInterface {
    return createSpaceMembershipMockConnectionWithHistory(queryResult: $queryResult, queryHistory: $unused);
}

/**
 * @param array<array<string, mixed>> $queryResult
 * @param array<array{sql: string, bindings: array<mixed>}>|null $queryHistory
 */
function createSpaceMembershipMockConnectionWithHistory(
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

it('finds membership by user and space', function (): void {
    $membershipRow = [
        'id' => 1,
        'user_id' => 1,
        'space_id' => 2,
        'last_read_message_id' => null,
        'joined_at' => '2026-02-24 00:00:00',
    ];

    $connection = createSpaceMembershipMockConnection(queryResult: [$membershipRow]);
    $metadataFactory = new EntityMetadataFactory();
    $hydrator = new EntityHydrator();

    $repository = new SpaceMembershipRepository(
        connection: $connection,
        metadataFactory: $metadataFactory,
        hydrator: $hydrator,
    );

    $membership = $repository->findByUserAndSpace(userId: 1, spaceId: 2);

    expect($membership)->toBeInstanceOf(SpaceMembership::class)
        ->and($membership->userId)->toBe(1)
        ->and($membership->spaceId)->toBe(2);
});

it('finds all memberships for a user', function (): void {
    $queryHistory = [];
    $rows = [
        [
            'id' => 1,
            'user_id' => 1,
            'space_id' => 2,
            'last_read_message_id' => null,
            'joined_at' => '2026-02-24 00:00:00',
        ],
        [
            'id' => 2,
            'user_id' => 1,
            'space_id' => 3,
            'last_read_message_id' => null,
            'joined_at' => '2026-02-24 00:00:00',
        ],
    ];

    $connection = createSpaceMembershipMockConnectionWithHistory(queryResult: $rows, queryHistory: $queryHistory);
    $metadataFactory = new EntityMetadataFactory();
    $hydrator = new EntityHydrator();

    $repository = new SpaceMembershipRepository(
        connection: $connection,
        metadataFactory: $metadataFactory,
        hydrator: $hydrator,
    );

    $memberships = $repository->findAllForUser(userId: 1);

    expect($memberships)->toHaveCount(2)
        ->and($memberships[0])->toBeInstanceOf(SpaceMembership::class)
        ->and($memberships[0]->userId)->toBe(1)
        ->and($memberships[1]->spaceId)->toBe(3)
        ->and($queryHistory[0]['sql'])->toContain('user_id = ?')
        ->and($queryHistory[0]['bindings'])->toContain(1);
});

it('creates a new membership when a user joins a space', function (): void {
    $queryHistory = [];
    $connection = createSpaceMembershipMockConnectionWithHistory(queryResult: [], queryHistory: $queryHistory);
    $metadataFactory = new EntityMetadataFactory();
    $hydrator = new EntityHydrator();

    $repository = new SpaceMembershipRepository(
        connection: $connection,
        metadataFactory: $metadataFactory,
        hydrator: $hydrator,
    );

    $membership = new SpaceMembership(
        id: null,
        userId: 1,
        spaceId: 2,
        lastReadMessageId: null,
        joinedAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );

    $repository->save(entity: $membership);

    expect($queryHistory)->toHaveCount(1)
        ->and($queryHistory[0]['sql'])->toContain('INSERT INTO space_memberships')
        ->and($queryHistory[0]['sql'])->toContain('user_id')
        ->and($queryHistory[0]['sql'])->toContain('space_id')
        ->and($queryHistory[0]['sql'])->toContain('joined_at');
});

it('deletes membership when a user leaves a space', function (): void {
    $queryHistory = [];
    $connection = createSpaceMembershipMockConnectionWithHistory(queryResult: [], queryHistory: $queryHistory);
    $metadataFactory = new EntityMetadataFactory();
    $hydrator = new EntityHydrator();

    $repository = new SpaceMembershipRepository(
        connection: $connection,
        metadataFactory: $metadataFactory,
        hydrator: $hydrator,
    );

    $membership = new SpaceMembership(
        id: 5,
        userId: 1,
        spaceId: 2,
        lastReadMessageId: null,
        joinedAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );

    $repository->delete(entity: $membership);

    expect($queryHistory)->toHaveCount(1)
        ->and($queryHistory[0]['sql'])->toContain('DELETE FROM space_memberships')
        ->and($queryHistory[0]['bindings'])->toContain(5);
});

it('updates last_read_message_id for a membership', function (): void {
    $queryHistory = [];
    $connection = createSpaceMembershipMockConnectionWithHistory(queryResult: [], queryHistory: $queryHistory);
    $metadataFactory = new EntityMetadataFactory();
    $hydrator = new EntityHydrator();

    $repository = new SpaceMembershipRepository(
        connection: $connection,
        metadataFactory: $metadataFactory,
        hydrator: $hydrator,
    );

    $membership = new SpaceMembership(
        id: 5,
        userId: 1,
        spaceId: 2,
        lastReadMessageId: null,
        joinedAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );

    $repository->updateLastReadMessageId(membership: $membership, messageId: 42);

    expect($membership->lastReadMessageId)->toBe(42)
        ->and($queryHistory)->toHaveCount(1)
        ->and($queryHistory[0]['sql'])->toContain('UPDATE space_memberships')
        ->and($queryHistory[0]['sql'])->toContain('last_read_message_id');
});
