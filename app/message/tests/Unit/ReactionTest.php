<?php

declare(strict_types=1);

use App\Message\Entity\Reaction;
use App\Message\Repository\ReactionRepository;
use App\Message\Repository\ReactionRepositoryInterface;
use Marko\Database\Connection\ConnectionInterface;
use Marko\Database\Connection\StatementInterface;
use Marko\Database\Entity\EntityHydrator;
use Marko\Database\Entity\EntityMetadataFactory;
use Marko\Database\Query\QueryBuilderFactoryInterface;
use Marko\Database\Query\QueryBuilderInterface;

it('creates a Reaction entity with all required fields', function (): void {
    $reaction = new Reaction(
        id: null,
        messageId: 1,
        userId: 2,
        emoji: '👍',
    );

    expect($reaction->id)->toBeNull()
        ->and($reaction->messageId)->toBe(1)
        ->and($reaction->userId)->toBe(2)
        ->and($reaction->emoji)->toBe('👍');
});

it('has a migration with UNIQUE constraint on message_id, user_id, and emoji', function (): void {
    $migrationFile = dirname(__DIR__, 4) . '/database/migrations/20260225191742_create_reactions.php';

    expect(file_exists(filename: $migrationFile))->toBeTrue();

    $content = file_get_contents(filename: $migrationFile);

    expect($content)
        ->toContain('reactions')
        ->toContain('message_id')
        ->toContain('user_id')
        ->toContain('emoji')
        ->toContain('PRIMARY KEY')
        ->toContain('UNIQUE')
        ->toContain('message_id')
        ->toContain('user_id')
        ->toContain('emoji')
        ->toContain('DROP TABLE');
});

it('finds all reactions for a message', function (): void {
    $reactionRows = [
        createReactionRow(id: 1, messageId: 5, userId: 1, emoji: '👍'),
        createReactionRow(id: 2, messageId: 5, userId: 2, emoji: '❤️'),
    ];

    $queryHistory = [];
    $connection = createReactionMockConnectionWithHistory($reactionRows, $queryHistory);
    $queryBuilderFactory = createReactionMockQueryBuilderFactory($connection);

    $repository = new ReactionRepository(
        connection: $connection,
        metadataFactory: new EntityMetadataFactory(),
        hydrator: new EntityHydrator(),
        queryBuilderFactory: $queryBuilderFactory,
    );

    $reactions = $repository->findByMessage(messageId: 5);

    expect($reactions)->toHaveCount(2)
        ->and($reactions[0])->toBeInstanceOf(Reaction::class)
        ->and($reactions[0]->emoji)->toBe('👍')
        ->and($queryHistory[0]['sql'])->toContain('"message_id" = $1')
        ->and($queryHistory[0]['bindings'])->toContain(5);
});

it('groups reactions by emoji with counts', function (): void {
    $groupedRows = [
        ['emoji' => '👍', 'count' => 3, 'user_reacted' => 1],
        ['emoji' => '❤️', 'count' => 1, 'user_reacted' => 0],
    ];

    $queryHistory = [];
    $connection = createReactionMockConnectionWithHistory($groupedRows, $queryHistory);
    $queryBuilderFactory = createReactionMockQueryBuilderFactory($connection);

    $repository = new ReactionRepository(
        connection: $connection,
        metadataFactory: new EntityMetadataFactory(),
        hydrator: new EntityHydrator(),
        queryBuilderFactory: $queryBuilderFactory,
    );

    $grouped = $repository->findGrouped(messageId: 5, userId: 1);

    expect($grouped)->toHaveCount(2)
        ->and($grouped[0]['emoji'])->toBe('👍')
        ->and($grouped[0]['count'])->toBe(3)
        ->and($grouped[0]['user_reacted'])->toBeTrue()
        ->and($grouped[1]['emoji'])->toBe('❤️')
        ->and($grouped[1]['count'])->toBe(1)
        ->and($grouped[1]['user_reacted'])->toBeFalse()
        ->and($queryHistory[0]['sql'])->toContain('GROUP BY emoji')
        ->and($queryHistory[0]['bindings'])->toContain(1)
        ->and($queryHistory[0]['bindings'])->toContain(5);
});

it('adds a new reaction', function (): void {
    $queryHistory = [];
    $connection = createReactionMockConnectionWithHistory([], $queryHistory);

    $repository = new ReactionRepository(
        connection: $connection,
        metadataFactory: new EntityMetadataFactory(),
        hydrator: new EntityHydrator(),
    );

    $repository->add(messageId: 5, userId: 1, emoji: '👍');

    expect($queryHistory)->toHaveCount(1)
        ->and($queryHistory[0]['sql'])->toContain('INSERT INTO reactions')
        ->and($queryHistory[0]['bindings'])->toContain(5)
        ->and($queryHistory[0]['bindings'])->toContain(1)
        ->and($queryHistory[0]['bindings'])->toContain('👍');
});

it('removes an existing reaction', function (): void {
    $queryHistory = [];
    $connection = createReactionMockConnectionWithHistory([], $queryHistory);
    $queryBuilderFactory = createReactionMockQueryBuilderFactory($connection);

    $repository = new ReactionRepository(
        connection: $connection,
        metadataFactory: new EntityMetadataFactory(),
        hydrator: new EntityHydrator(),
        queryBuilderFactory: $queryBuilderFactory,
    );

    $repository->remove(messageId: 5, userId: 1, emoji: '👍');

    expect($queryHistory)->toHaveCount(1)
        ->and($queryHistory[0]['sql'])->toContain('DELETE FROM reactions')
        ->and($queryHistory[0]['sql'])->toContain('"message_id" = $1')
        ->and($queryHistory[0]['sql'])->toContain('"user_id" = $2')
        ->and($queryHistory[0]['sql'])->toContain('"emoji" = $3')
        ->and($queryHistory[0]['bindings'])->toContain(5)
        ->and($queryHistory[0]['bindings'])->toContain(1)
        ->and($queryHistory[0]['bindings'])->toContain('👍');
});

it('finds grouped reactions using raw query through query builder', function (): void {
    $groupedRows = [
        ['emoji' => '👍', 'count' => 2, 'user_reacted' => 1],
    ];

    $queryHistory = [];
    $connection = createReactionMockConnectionWithHistory($groupedRows, $queryHistory);
    $queryBuilderFactory = createReactionMockQueryBuilderFactory($connection);

    $repository = new ReactionRepository(
        connection: $connection,
        metadataFactory: new EntityMetadataFactory(),
        hydrator: new EntityHydrator(),
        queryBuilderFactory: $queryBuilderFactory,
    );

    $grouped = $repository->findGrouped(messageId: 5, userId: 1);

    expect($grouped)->toHaveCount(1)
        ->and($grouped[0]['emoji'])->toBe('👍')
        ->and($grouped[0]['count'])->toBe(2)
        ->and($grouped[0]['user_reacted'])->toBeTrue()
        ->and($queryHistory[0]['sql'])->toContain('GROUP BY emoji')
        ->and($queryHistory[0]['bindings'])->toContain(1)
        ->and($queryHistory[0]['bindings'])->toContain(5);
});

it('removes a reaction using query builder delete instead of raw SQL', function (): void {
    $queryHistory = [];
    $connection = createReactionMockConnectionWithHistory([], $queryHistory);
    $queryBuilderFactory = createReactionMockQueryBuilderFactory($connection);

    $repository = new ReactionRepository(
        connection: $connection,
        metadataFactory: new EntityMetadataFactory(),
        hydrator: new EntityHydrator(),
        queryBuilderFactory: $queryBuilderFactory,
    );

    $repository->remove(messageId: 5, userId: 1, emoji: '👍');

    expect($queryHistory)->toHaveCount(1)
        ->and($queryHistory[0]['sql'])->toContain('DELETE FROM reactions')
        ->and($queryHistory[0]['bindings'])->toContain(5)
        ->and($queryHistory[0]['bindings'])->toContain(1)
        ->and($queryHistory[0]['bindings'])->toContain('👍');
});

it('finds reactions by message using query builder instead of raw SQL', function (): void {
    $reactionRows = [
        createReactionRow(id: 1, messageId: 5, userId: 1, emoji: '👍'),
    ];

    $queryHistory = [];
    $connection = createReactionMockConnectionWithHistory($reactionRows, $queryHistory);
    $queryBuilderFactory = createReactionMockQueryBuilderFactory($connection);

    $repository = new ReactionRepository(
        connection: $connection,
        metadataFactory: new EntityMetadataFactory(),
        hydrator: new EntityHydrator(),
        queryBuilderFactory: $queryBuilderFactory,
    );

    $reactions = $repository->findByMessage(messageId: 5);

    expect($reactions)->toHaveCount(1)
        ->and($reactions[0])->toBeInstanceOf(Reaction::class)
        ->and($reactions[0]->emoji)->toBe('👍');
});

// Helper functions

function createReactionRow(
    int $id = 1,
    int $messageId = 1,
    int $userId = 1,
    string $emoji = '👍',
): array {
    return [
        'id' => $id,
        'message_id' => $messageId,
        'user_id' => $userId,
        'emoji' => $emoji,
    ];
}

/**
 * @param array<array<string, mixed>> $queryResult
 * @param array<array{sql: string, bindings: array<mixed>}>|null $queryHistory
 */
function createReactionMockConnectionWithHistory(
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

function createReactionMockQueryBuilder(
    ConnectionInterface $connection,
): QueryBuilderInterface {
    return new class ($connection) implements QueryBuilderInterface
    {
        private string $table = '';

        /** @var array<array{column: string, operator: string, value: mixed}> */
        private array $wheres = [];

        public function __construct(
            private readonly ConnectionInterface $connection,
        ) {}

        public function table(string $table): static
        {
            $this->table = $table;

            return $this;
        }

        public function select(string ...$columns): static
        {
            return $this;
        }

        public function where(string $column, string $operator, mixed $value): static
        {
            $this->wheres[] = ['column' => $column, 'operator' => $operator, 'value' => $value];

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
            $conditions = array_map(
                callback: fn (array $w): string => sprintf('"%s" = $%d', $w['column'], array_search(needle: $w, haystack: $this->wheres, strict: true) + 1),
                array: $this->wheres,
            );
            $bindings = array_map(callback: fn (array $w): mixed => $w['value'], array: $this->wheres);
            $where = count(value: $conditions) > 0 ? ' WHERE ' . implode(separator: ' AND ', array: $conditions) : '';
            $sql = sprintf('SELECT * FROM %s%s', $this->table, $where);

            return $this->connection->query(sql: $sql, bindings: $bindings);
        }

        public function first(): ?array
        {
            return $this->get()[0] ?? null;
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
            $conditions = array_map(
                callback: fn (array $w): string => sprintf('"%s" = $%d', $w['column'], array_search(needle: $w, haystack: $this->wheres, strict: true) + 1),
                array: $this->wheres,
            );
            $bindings = array_map(callback: fn (array $w): mixed => $w['value'], array: $this->wheres);
            $where = count(value: $conditions) > 0 ? ' WHERE ' . implode(separator: ' AND ', array: $conditions) : '';
            $sql = sprintf('DELETE FROM %s%s', $this->table, $where);

            $this->connection->execute(sql: $sql, bindings: $bindings);

            return 1;
        }

        public function count(): int
        {
            return 0;
        }

        public function raw(string $sql, array $bindings = []): array
        {
            return $this->connection->query(sql: $sql, bindings: $bindings);
        }
    };
}

function createReactionMockQueryBuilderFactory(
    ConnectionInterface $connection,
): QueryBuilderFactoryInterface {
    return new class ($connection) implements QueryBuilderFactoryInterface
    {
        public function __construct(
            private readonly ConnectionInterface $connection,
        ) {}

        public function create(): QueryBuilderInterface
        {
            return createReactionMockQueryBuilder($this->connection);
        }
    };
}
