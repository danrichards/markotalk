<?php

declare(strict_types=1);

use App\Space\Entity\SpaceMembership;
use App\Space\Repository\SpaceMembershipRepository;
use Marko\Database\Connection\ConnectionInterface;
use Marko\Database\Connection\StatementInterface;
use Marko\Database\Entity\EntityHydrator;
use Marko\Database\Entity\EntityMetadataFactory;
use Marko\Database\Query\QueryBuilderFactoryInterface;
use Marko\Database\Query\QueryBuilderInterface;

// ─── Mock helpers ─────────────────────────────────────────────────────────────

/**
 * @param array<array<string, mixed>> $queryResult
 * @param array<array{sql: string, bindings: array<mixed>}>|null $queryHistory
 */
function createRefactorMockConnection(
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

/**
 * Creates a QueryBuilderInterface mock that records calls and returns the given rows on get().
 *
 * @param array<array<string, mixed>> $rows
 * @param array<string>|null $callLog
 */
function createRefactorMockQueryBuilder(
    array $rows = [],
    ?array &$callLog = null,
    ?array &$updateData = null,
    ?array &$whereArgs = null,
): QueryBuilderInterface {
    $callLog ??= [];
    $updateData ??= [];
    $whereArgs ??= [];

    return new class ($rows, $callLog, $updateData, $whereArgs) implements QueryBuilderInterface
    {
        /** @var array<string> */
        private array $wheres = [];

        /**
         * @param array<array<string, mixed>> $rows
         * @param array<string> $callLog
         * @param array<string, mixed> $updateData
         * @param array<mixed> $whereArgs
         */
        public function __construct(
            private readonly array $rows,
            private array &$callLog,
            private array &$updateData,
            private array &$whereArgs,
        ) {}

        public function table(string $table): static
        {
            $this->callLog[] = 'table:' . $table;

            return $this;
        }

        public function select(string ...$columns): static
        {
            return $this;
        }

        public function where(string $column, string $operator, mixed $value): static
        {
            $this->callLog[] = 'where:' . $column . $operator . $value;
            $this->whereArgs[] = ['column' => $column, 'operator' => $operator, 'value' => $value];

            return $this;
        }

        public function whereIn(string $column, array $values): static
        {
            return $this;
        }

        public function whereNull(string $column): static
        {
            return $this;
        }

        public function whereNotNull(string $column): static
        {
            return $this;
        }

        public function orWhere(string $column, string $operator, mixed $value): static
        {
            return $this;
        }

        public function join(string $table, string $first, string $operator, string $second): static
        {
            return $this;
        }

        public function leftJoin(string $table, string $first, string $operator, string $second): static
        {
            return $this;
        }

        public function rightJoin(string $table, string $first, string $operator, string $second): static
        {
            return $this;
        }

        public function orderBy(string $column, string $direction = 'ASC'): static
        {
            return $this;
        }

        public function limit(int $limit): static
        {
            return $this;
        }

        public function offset(int $offset): static
        {
            return $this;
        }

        public function get(): array
        {
            $this->callLog[] = 'get';

            return $this->rows;
        }

        public function first(): ?array
        {
            return $this->rows[0] ?? null;
        }

        public function insert(array $data): int
        {
            return 1;
        }

        public function update(array $data): int
        {
            $this->callLog[] = 'update';
            $this->updateData = $data;

            return 1;
        }

        public function delete(): int
        {
            return 0;
        }

        public function count(): int
        {
            return count(value: $this->rows);
        }

        public function raw(string $sql, array $bindings = []): array
        {
            $this->callLog[] = 'raw:' . $sql;

            return $this->rows;
        }
    };
}

/**
 * @param array<array<string, mixed>> $rows
 * @param array<string>|null $callLog
 * @param array<string, mixed>|null $updateData
 * @param array<mixed>|null $whereArgs
 */
function createRefactorMockFactory(
    array $rows = [],
    ?array &$callLog = null,
    ?array &$updateData = null,
    ?array &$whereArgs = null,
): QueryBuilderFactoryInterface {
    $builder = createRefactorMockQueryBuilder(
        rows: $rows,
        callLog: $callLog,
        updateData: $updateData,
        whereArgs: $whereArgs,
    );

    return new class ($builder) implements QueryBuilderFactoryInterface
    {
        public function __construct(
            private readonly QueryBuilderInterface $builder,
        ) {}

        public function create(): QueryBuilderInterface
        {
            return $this->builder;
        }
    };
}

/**
 * @param array<array<string, mixed>> $connectionRows
 * @param array<array<string, mixed>> $builderRows
 * @param array<string>|null $callLog
 * @param array<string, mixed>|null $updateData
 * @param array<mixed>|null $whereArgs
 * @param array<array{sql: string, bindings: array<mixed>}>|null $queryHistory
 */
function createRefactorRepository(
    array $connectionRows = [],
    array $builderRows = [],
    ?array &$callLog = null,
    ?array &$updateData = null,
    ?array &$whereArgs = null,
    ?array &$queryHistory = null,
): SpaceMembershipRepository {
    $connection = createRefactorMockConnection(
        queryResult: $connectionRows,
        queryHistory: $queryHistory,
    );
    $factory = createRefactorMockFactory(
        rows: $builderRows,
        callLog: $callLog,
        updateData: $updateData,
        whereArgs: $whereArgs,
    );

    return new SpaceMembershipRepository(
        connection: $connection,
        metadataFactory: new EntityMetadataFactory(),
        hydrator: new EntityHydrator(),
        queryBuilderFactory: $factory,
    );
}

// ─── Tests ────────────────────────────────────────────────────────────────────

it('clears last read message id using query builder bulk update', function (): void {
    $callLog = [];
    $updateData = [];
    $whereArgs = [];
    $repository = createRefactorRepository(
        callLog: $callLog,
        updateData: $updateData,
        whereArgs: $whereArgs,
    );

    $repository->clearLastReadMessageId(messageId: 42);

    expect($whereArgs[0]['column'])->toBe('last_read_message_id')
        ->and($whereArgs[0]['operator'])->toBe('=')
        ->and($whereArgs[0]['value'])->toBe(42)
        ->and($callLog)->toContain('update')
        ->and($updateData)->toBe(['last_read_message_id' => null]);
});

it('updates last read message id using save instead of raw SQL', function (): void {
    $membershipRow = [
        'id' => 5,
        'user_id' => 1,
        'space_id' => 2,
        'last_read_message_id' => null,
        'joined_at' => '2026-02-24 00:00:00',
    ];

    $queryHistory = [];
    // Connection returns the membership row so findByUserAndSpace hydrates it (establishing a dirty-tracking snapshot)
    $repository = createRefactorRepository(
        connectionRows: [$membershipRow],
        queryHistory: $queryHistory,
    );

    // Fetch a hydrated entity so the hydrator has a dirty-tracking snapshot for it
    $membership = $repository->findByUserAndSpace(userId: 1, spaceId: 2);

    // Reset history so only the save() call is captured
    $queryHistory = [];

    $repository->updateLastReadMessageId(membership: $membership, messageId: 99);

    // Entity property must be updated
    expect($membership->lastReadMessageId)->toBe(99)
        // save() issues an UPDATE through dirty-tracking — only last_read_message_id is dirty
        ->and($queryHistory)->toHaveCount(1)
        ->and($queryHistory[0]['sql'])->toContain('UPDATE space_memberships')
        ->and($queryHistory[0]['sql'])->toContain('last_read_message_id')
        // save() produces SET clause with only the dirty column; bindings are [newValue, id]
        ->and($queryHistory[0]['bindings'])->toBe([99, 5]);
});

it('counts unread messages using raw query for cross-table access', function (): void {
    $membershipRow = [
        'id' => 1,
        'user_id' => 1,
        'space_id' => 2,
        'last_read_message_id' => 10,
        'joined_at' => '2026-02-24 00:00:00',
    ];

    $callLog = [];
    // Builder rows returned for the raw() call (the cross-table count)
    $builderRows = [['count' => 5]];

    // Connection returns the membership on the first query (findByUserAndSpace uses findBy -> connection->query)
    $connectionRows = [$membershipRow];
    $repository = createRefactorRepository(
        connectionRows: $connectionRows,
        builderRows: $builderRows,
        callLog: $callLog,
    );

    $count = $repository->countUnread(userId: 1, spaceId: 2);

    expect($count)->toBe(5)
        ->and(array_filter(array: $callLog, callback: fn (string $e) => str_starts_with(haystack: $e, needle: 'raw:')))->not->toBeEmpty();
});

it('finds all memberships for a space using query builder', function (): void {
    $rows = [
        [
            'id' => 1,
            'user_id' => 10,
            'space_id' => 7,
            'last_read_message_id' => null,
            'joined_at' => '2026-02-24 00:00:00',
        ],
        [
            'id' => 2,
            'user_id' => 11,
            'space_id' => 7,
            'last_read_message_id' => null,
            'joined_at' => '2026-02-24 00:00:00',
        ],
    ];

    $callLog = [];
    $whereArgs = [];
    $repository = createRefactorRepository(
        builderRows: $rows,
        callLog: $callLog,
        whereArgs: $whereArgs,
    );

    $memberships = $repository->findAllForSpace(spaceId: 7);

    expect($memberships)->toHaveCount(2)
        ->and($memberships[0])->toBeInstanceOf(SpaceMembership::class)
        ->and($memberships[0]->spaceId)->toBe(7)
        ->and($memberships[1]->userId)->toBe(11)
        ->and($whereArgs[0]['column'])->toBe('space_id')
        ->and($whereArgs[0]['value'])->toBe(7)
        ->and($callLog)->toContain('get');
});

it('finds all memberships for a user using query builder', function (): void {
    $rows = [
        [
            'id' => 1,
            'user_id' => 5,
            'space_id' => 10,
            'last_read_message_id' => null,
            'joined_at' => '2026-02-24 00:00:00',
        ],
        [
            'id' => 2,
            'user_id' => 5,
            'space_id' => 20,
            'last_read_message_id' => null,
            'joined_at' => '2026-02-24 00:00:00',
        ],
    ];

    $callLog = [];
    $whereArgs = [];
    $repository = createRefactorRepository(
        builderRows: $rows,
        callLog: $callLog,
        whereArgs: $whereArgs,
    );

    $memberships = $repository->findAllForUser(userId: 5);

    expect($memberships)->toHaveCount(2)
        ->and($memberships[0])->toBeInstanceOf(SpaceMembership::class)
        ->and($memberships[0]->userId)->toBe(5)
        ->and($memberships[1]->spaceId)->toBe(20)
        ->and($whereArgs[0]['column'])->toBe('user_id')
        ->and($whereArgs[0]['value'])->toBe(5)
        ->and($callLog)->toContain('get');
});
