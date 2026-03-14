<?php

declare(strict_types=1);

use App\Message\Entity\Message;
use App\Message\Repository\MessageRepository;
use Marko\Core\Event\Event;
use Marko\Core\Event\EventDispatcherInterface;
use Marko\Database\Connection\ConnectionInterface;
use Marko\Database\Connection\StatementInterface;
use Marko\Database\Entity\EntityHydrator;
use Marko\Database\Entity\EntityMetadataFactory;
use Marko\Database\Query\QueryBuilderFactoryInterface;
use Marko\Database\Query\QueryBuilderInterface;

// Helper factories

function makeQbMessageRow(
    int $id = 1,
    int $spaceId = 1,
    int $userId = 2,
    string $body = 'Hello',
    string $bodyHtml = '<p>Hello</p>',
    bool $isPinned = false,
    ?string $editedAt = null,
    string $createdAt = '2026-02-24 00:00:00',
): array {
    return [
        'id' => $id,
        'space_id' => $spaceId,
        'user_id' => $userId,
        'body' => $body,
        'body_html' => $bodyHtml,
        'is_pinned' => $isPinned ? 1 : 0,
        'edited_at' => $editedAt,
        'created_at' => $createdAt,
    ];
}

function makeQbMockConnection(): ConnectionInterface
{
    return new class () implements ConnectionInterface {
        public function connect(): void {}

        public function disconnect(): void {}

        public function isConnected(): bool
        {
            return true;
        }

        public function query(
            string $sql,
            array $bindings = [],
        ): array {
            return [];
        }

        public function execute(
            string $sql,
            array $bindings = [],
        ): int {
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

function makeQbMockDispatcher(): EventDispatcherInterface
{
    return new class () implements EventDispatcherInterface {
        public function dispatch(Event $event): void {}
    };
}

/**
 * Create a spy QueryBuilderInterface that records method calls and returns configurable results.
 *
 * @param array<array<string, mixed>> $rows Rows to return from get()
 */
function makeSpyQueryBuilder(array $rows = []): QueryBuilderInterface
{
    return new class ($rows) implements QueryBuilderInterface {
        /** @var array<string, array<int, mixed>> */
        public array $calls = [];

        public function __construct(
            private readonly array $rows,
        ) {}

        public function table(string $table): static
        {
            $this->calls['table'][] = $table;

            return $this;
        }

        public function select(string ...$columns): static
        {
            $this->calls['select'][] = $columns;

            return $this;
        }

        public function where(string $column, string $operator, mixed $value): static
        {
            $this->calls['where'][] = [$column, $operator, $value];

            return $this;
        }

        public function whereIn(string $column, array $values): static
        {
            $this->calls['whereIn'][] = [$column, $values];

            return $this;
        }

        public function whereNull(string $column): static
        {
            $this->calls['whereNull'][] = $column;

            return $this;
        }

        public function whereNotNull(string $column): static
        {
            $this->calls['whereNotNull'][] = $column;

            return $this;
        }

        public function orWhere(string $column, string $operator, mixed $value): static
        {
            $this->calls['orWhere'][] = [$column, $operator, $value];

            return $this;
        }

        public function join(string $table, string $first, string $operator, string $second): static
        {
            $this->calls['join'][] = [$table, $first, $operator, $second];

            return $this;
        }

        public function leftJoin(string $table, string $first, string $operator, string $second): static
        {
            $this->calls['leftJoin'][] = [$table, $first, $operator, $second];

            return $this;
        }

        public function rightJoin(string $table, string $first, string $operator, string $second): static
        {
            $this->calls['rightJoin'][] = [$table, $first, $operator, $second];

            return $this;
        }

        public function orderBy(string $column, string $direction = 'ASC'): static
        {
            $this->calls['orderBy'][] = [$column, $direction];

            return $this;
        }

        public function limit(int $limit): static
        {
            $this->calls['limit'][] = $limit;

            return $this;
        }

        public function offset(int $offset): static
        {
            $this->calls['offset'][] = $offset;

            return $this;
        }

        public function get(): array
        {
            $this->calls['get'][] = true;

            return $this->rows;
        }

        public function first(): ?array
        {
            $this->calls['first'][] = true;

            return $this->rows[0] ?? null;
        }

        public function insert(array $data): int
        {
            return 1;
        }

        public function update(array $data): int
        {
            return 1;
        }

        public function delete(): int
        {
            return 1;
        }

        public function count(): int
        {
            return count(value: $this->rows);
        }

        public function raw(string $sql, array $bindings = []): array
        {
            return [];
        }
    };
}

/**
 * Create a QueryBuilderFactoryInterface that returns the given spy builder.
 */
function makeQbFactory(QueryBuilderInterface $builder): QueryBuilderFactoryInterface
{
    return new readonly class ($builder) implements QueryBuilderFactoryInterface {
        public function __construct(
            private QueryBuilderInterface $builder,
        ) {}

        public function create(): QueryBuilderInterface
        {
            return $this->builder;
        }
    };
}

function makeQbRepository(
    QueryBuilderInterface $queryBuilder,
): MessageRepository {
    return new MessageRepository(
        connection: makeQbMockConnection(),
        metadataFactory: new EntityMetadataFactory(),
        hydrator: new EntityHydrator(),
        queryBuilderFactory: makeQbFactory(builder: $queryBuilder),
        eventDispatcher: makeQbMockDispatcher(),
    );
}

// Tests

it('finds pinned messages by space using query builder instead of raw SQL', function (): void {
    $rows = [
        makeQbMessageRow(id: 1, isPinned: true),
        makeQbMessageRow(id: 2, isPinned: true),
    ];
    $spy = makeSpyQueryBuilder(rows: $rows);
    $repository = makeQbRepository(queryBuilder: $spy);

    $result = $repository->findPinnedBySpace(spaceId: 5);

    expect($result)->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(Message::class)
        ->and($spy->calls['where'])->toContain(['space_id', '=', 5])
        ->and($spy->calls['where'])->toContain(['is_pinned', '=', true])
        ->and($spy->calls['orderBy'])->toContain(['id', 'ASC'])
        ->and(array_key_exists('get', $spy->calls))->toBeTrue();
});

it('finds paginated messages using query builder with conditional cursor where clause', function (): void {
    $rows = [
        makeQbMessageRow(id: 3),
        makeQbMessageRow(id: 2),
        makeQbMessageRow(id: 1),
    ];
    $spyNoCursor = makeSpyQueryBuilder(rows: $rows);
    $repositoryNoCursor = makeQbRepository(queryBuilder: $spyNoCursor);

    $paginator = $repositoryNoCursor->findPaginated(spaceId: 7, perPage: 50);

    expect($paginator->items())->toHaveCount(3)
        ->and($spyNoCursor->calls['where'])->toContain(['space_id', '=', 7])
        ->and($spyNoCursor->calls['orderBy'])->toContain(['id', 'DESC'])
        ->and($spyNoCursor->calls['limit'])->toContain(51);

    // With cursor: adds id < cursorId where clause
    $spyCursor = makeSpyQueryBuilder(rows: $rows);
    $repositoryCursor = makeQbRepository(queryBuilder: $spyCursor);
    $cursor = base64_encode(string: json_encode(value: ['id' => 10]));

    $repositoryCursor->findPaginated(spaceId: 7, perPage: 50, cursor: $cursor);

    expect($spyCursor->calls['where'])->toContain(['space_id', '=', 7])
        ->and($spyCursor->calls['where'])->toContain(['id', '<', 10])
        ->and($spyCursor->calls['orderBy'])->toContain(['id', 'DESC']);
});

it('finds messages by space since a given id using query builder', function (): void {
    $rows = [
        makeQbMessageRow(id: 6),
        makeQbMessageRow(id: 7),
    ];
    $spy = makeSpyQueryBuilder(rows: $rows);
    $repository = makeQbRepository(queryBuilder: $spy);

    $result = $repository->findBySpaceSince(spaceId: 4, sinceId: 5);

    expect($result)->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(Message::class)
        ->and($spy->calls['where'])->toContain(['space_id', '=', 4])
        ->and($spy->calls['where'])->toContain(['id', '>', 5])
        ->and($spy->calls['orderBy'])->toContain(['id', 'ASC'])
        ->and(array_key_exists('get', $spy->calls))->toBeTrue();
});

it('finds messages by space with limit using query builder instead of raw SQL', function (): void {
    $rows = [
        makeQbMessageRow(id: 1),
        makeQbMessageRow(id: 2),
    ];
    $spy = makeSpyQueryBuilder(rows: $rows);
    $repository = makeQbRepository(queryBuilder: $spy);

    $result = $repository->findBySpace(spaceId: 3, limit: 10);

    expect($result)->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(Message::class)
        ->and($spy->calls['where'])->toContain(['space_id', '=', 3])
        ->and($spy->calls['orderBy'])->toContain(['id', 'ASC'])
        ->and($spy->calls['limit'])->toContain(10)
        ->and(array_key_exists('get', $spy->calls))->toBeTrue();
});

it('finds edited messages since a timestamp using query builder', function (): void {
    $rows = [
        makeQbMessageRow(id: 10, editedAt: '2026-03-14 10:00:00'),
    ];
    $spy = makeSpyQueryBuilder(rows: $rows);
    $repository = makeQbRepository(queryBuilder: $spy);

    $since = new DateTimeImmutable('2026-03-14 09:00:00');

    $result = $repository->findEditedSince(spaceId: 2, since: $since);

    expect($result)->toHaveCount(1)
        ->and($result[0])->toBeInstanceOf(Message::class)
        ->and($spy->calls['where'])->toContain(['space_id', '=', 2])
        ->and($spy->calls['where'])->toContain(['edited_at', '>', '2026-03-14 09:00:00'])
        ->and($spy->calls['orderBy'])->toContain(['id', 'ASC'])
        ->and(array_key_exists('get', $spy->calls))->toBeTrue();
});
