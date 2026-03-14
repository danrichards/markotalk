<?php

declare(strict_types=1);

use App\Message\Entity\Message;
use App\Message\Repository\MessageRepository;
use App\Message\Repository\MessageRepositoryInterface;
use Marko\Core\Event\EventDispatcherInterface;
use Marko\Database\Connection\ConnectionInterface;
use Marko\Database\Connection\StatementInterface;
use Marko\Database\Entity\EntityHydrator;
use Marko\Database\Entity\EntityMetadataFactory;
use Marko\Database\Query\QueryBuilderFactoryInterface;
use Marko\Database\Query\QueryBuilderInterface;

it('creates a Message entity with all required fields', function (): void {
    $message = new Message(
        id: null,
        spaceId: 1,
        userId: 2,
        body: 'Hello world',
        bodyHtml: '<p>Hello world</p>',
        isPinned: false,
        editedAt: null,
        createdAt: new DateTimeImmutable('2026-02-24 00:00:00'),
    );

    expect($message->id)->toBeNull()
        ->and($message->spaceId)->toBe(1)
        ->and($message->userId)->toBe(2)
        ->and($message->body)->toBe('Hello world')
        ->and($message->bodyHtml)->toBe('<p>Hello world</p>')
        ->and($message->isPinned)->toBeFalse()
        ->and($message->editedAt)->toBeNull()
        ->and($message->createdAt)->toBeInstanceOf(DateTimeImmutable::class);
});

it('has a migration with composite index on space_id and id', function (): void {
    $migrationFile = dirname(__DIR__, 4) . '/database/migrations/20260225191741_create_messages.php';

    expect(file_exists(filename: $migrationFile))->toBeTrue();

    $content = file_get_contents(filename: $migrationFile);

    expect($content)
        ->toContain('messages')
        ->toContain('space_id')
        ->toContain('user_id')
        ->toContain('body')
        ->toContain('body_html')
        ->toContain('is_pinned')
        ->toContain('edited_at')
        ->toContain('created_at')
        ->toContain('PRIMARY KEY')
        ->toContain('idx_messages_space_id_id')
        ->toContain('DROP TABLE');
});

it('finds messages by space ordered by id ascending', function (): void {
    $messageRows = [
        createMessageRow(id: 1, spaceId: 1, body: 'First'),
        createMessageRow(id: 2, spaceId: 1, body: 'Second'),
    ];

    $spy = createMessageSpyQueryBuilder(rows: $messageRows);
    $dispatcher = createMockDispatcher();

    $repository = new MessageRepository(
        connection: createMessageMockConnection(),
        metadataFactory: new EntityMetadataFactory(),
        hydrator: new EntityHydrator(),
        queryBuilderFactory: createMessageQueryBuilderFactory(builder: $spy),
        eventDispatcher: $dispatcher,
    );

    $messages = $repository->findBySpace(spaceId: 1);

    expect($messages)->toHaveCount(2)
        ->and($messages[0])->toBeInstanceOf(Message::class)
        ->and($messages[0]->body)->toBe('First')
        ->and($spy->calls['where'])->toContain(['space_id', '=', 1])
        ->and($spy->calls['orderBy'])->toContain(['id', 'ASC']);
});

it('finds messages by space since a given message id', function (): void {
    $messageRows = [
        createMessageRow(id: 5, spaceId: 1, body: 'After message 3'),
    ];

    $spy = createMessageSpyQueryBuilder(rows: $messageRows);
    $dispatcher = createMockDispatcher();

    $repository = new MessageRepository(
        connection: createMessageMockConnection(),
        metadataFactory: new EntityMetadataFactory(),
        hydrator: new EntityHydrator(),
        queryBuilderFactory: createMessageQueryBuilderFactory(builder: $spy),
        eventDispatcher: $dispatcher,
    );

    $messages = $repository->findBySpaceSince(spaceId: 1, sinceId: 3);

    expect($messages)->toHaveCount(1)
        ->and($messages[0])->toBeInstanceOf(Message::class)
        ->and($messages[0]->body)->toBe('After message 3')
        ->and($spy->calls['where'])->toContain(['space_id', '=', 1])
        ->and($spy->calls['where'])->toContain(['id', '>', 3])
        ->and($spy->calls['orderBy'])->toContain(['id', 'ASC']);
});

it('saves a new message and dispatches MessageCreatedEvent', function (): void {
    $dispatchedEvents = [];
    $queryHistory = [];
    $connection = createMessageMockConnectionWithHistory([], $queryHistory);
    $dispatcher = createMockDispatcherWithHistory($dispatchedEvents);

    $repository = new MessageRepository(
        connection: $connection,
        metadataFactory: new EntityMetadataFactory(),
        hydrator: new EntityHydrator(),
        queryBuilderFactory: createMessageQueryBuilderFactory(builder: createMessageSpyQueryBuilder()),
        eventDispatcher: $dispatcher,
    );

    $message = new Message(
        id: null,
        spaceId: 1,
        userId: 2,
        body: 'Hello world',
        bodyHtml: '<p>Hello world</p>',
        isPinned: false,
        editedAt: null,
        createdAt: new DateTimeImmutable('2026-02-24 00:00:00'),
    );

    $repository->save(entity: $message);

    $domainEvents = array_values(array: array_filter(
        array: $dispatchedEvents,
        callback: fn ($e) => $e instanceof \App\Message\Event\MessageCreatedEvent,
    ));

    expect($queryHistory)->toHaveCount(1)
        ->and($queryHistory[0]['sql'])->toContain('INSERT INTO messages')
        ->and($domainEvents)->toHaveCount(1)
        ->and($domainEvents[0]->message)->toBe($message);
});

it('has module.php with MessageRepositoryInterface binding', function (): void {
    $modulePath = dirname(__DIR__, 2) . '/module.php';

    expect(file_exists(filename: $modulePath))->toBeTrue();

    $bindings = require $modulePath;

    expect($bindings)->toBeArray()
        ->and($bindings)->toHaveKey('bindings')
        ->and($bindings['bindings'])->toHaveKey(MessageRepositoryInterface::class)
        ->and($bindings['bindings'][MessageRepositoryInterface::class])->toBe(MessageRepository::class);
});

// Helper functions

function createMessageSpyQueryBuilder(array $rows = []): QueryBuilderInterface
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

function createMessageQueryBuilderFactory(QueryBuilderInterface $builder): QueryBuilderFactoryInterface
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

function createMessageRow(
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

function createMessageMockConnection(
    array $queryResult = [],
): ConnectionInterface {
    return createMessageMockConnectionWithHistory($queryResult, $unused);
}

/**
 * @param array<array<string, mixed>> $queryResult
 * @param array<array{sql: string, bindings: array<mixed>}>|null $queryHistory
 */
function createMessageMockConnectionWithHistory(
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

function createMockDispatcher(): EventDispatcherInterface
{
    return new class () implements EventDispatcherInterface
    {
        public function dispatch(\Marko\Core\Event\Event $event): void {}
    };
}

/**
 * @param array<\Marko\Core\Event\Event> $dispatchedEvents
 */
function createMockDispatcherWithHistory(
    array &$dispatchedEvents,
): EventDispatcherInterface {
    return new class ($dispatchedEvents) implements EventDispatcherInterface
    {
        /**
         * @param array<\Marko\Core\Event\Event> $dispatchedEvents
         */
        public function __construct(
            private array &$dispatchedEvents,
        ) {}

        public function dispatch(\Marko\Core\Event\Event $event): void
        {
            $this->dispatchedEvents[] = $event;
        }
    };
}
