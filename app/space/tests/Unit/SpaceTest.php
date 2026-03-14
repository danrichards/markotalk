<?php

declare(strict_types=1);

use App\Space\Entity\Space;
use App\Space\Repository\SpaceRepository;
use App\Space\Repository\SpaceRepositoryInterface;
use Marko\Database\Attributes\Column;
use Marko\Database\Attributes\Table;
use Marko\Database\Connection\ConnectionInterface;
use Marko\Database\Connection\StatementInterface;
use Marko\Database\Entity\Entity;
use Marko\Database\Entity\EntityHydrator;
use Marko\Database\Entity\EntityMetadataFactory;
use Marko\Database\Query\QueryBuilderFactoryInterface;
use Marko\Database\Query\QueryBuilderInterface;
use Marko\Database\Repository\Repository;
use Marko\Database\Repository\RepositoryInterface;

it('has a migration that creates the spaces table with all columns and indexes', function (): void {
    $migrationFile = dirname(path: __DIR__, levels: 4) . '/database/migrations/20260225191740_create_spaces.php';

    $content = file_get_contents(filename: $migrationFile);

    expect(file_exists(filename: $migrationFile))->toBeTrue()
        ->and($content)
        ->toContain('spaces')
        ->toContain('name')
        ->toContain('slug')
        ->toContain('description')
        ->toContain('is_archived')
        ->toContain('created_by')
        ->toContain('created_at')
        ->toContain('updated_at')
        ->toContain('PRIMARY KEY')
        ->toContain('UNIQUE')
        ->toContain('DROP TABLE');
});

it('creates a Space entity with all required fields', function (): void {
    $space = new Space(
        id: null,
        name: 'General',
        slug: 'general',
        description: 'The general discussion space',
        isArchived: false,
        createdBy: 1,
        createdAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
        updatedAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );

    expect($space->id)->toBeNull()
        ->and($space->name)->toBe('General')
        ->and($space->slug)->toBe('general')
        ->and($space->description)->toBe('The general discussion space')
        ->and($space->isArchived)->toBeFalse()
        ->and($space->createdBy)->toBe(1)
        ->and($space->createdAt)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($space->updatedAt)->toBeInstanceOf(DateTimeImmutable::class);
});

it('finds a space by slug', function (): void {
    $spaceRow = [
        'id' => 1,
        'name' => 'General',
        'slug' => 'general',
        'description' => 'The general discussion space',
        'is_archived' => 0,
        'created_by' => 1,
        'created_at' => '2026-02-24 00:00:00',
        'updated_at' => '2026-02-24 00:00:00',
    ];

    $connection = createSpaceMockConnection(queryResult: [$spaceRow]);
    $metadataFactory = new EntityMetadataFactory();
    $hydrator = new EntityHydrator();

    $repository = new SpaceRepository(
        connection: $connection,
        metadataFactory: $metadataFactory,
        hydrator: $hydrator,
    );

    $space = $repository->findBySlug(slug: 'general');

    expect($space)->toBeInstanceOf(Space::class)
        ->and($space->slug)->toBe('general')
        ->and($space->name)->toBe('General');
});

it('finds all active (non-archived) spaces', function (): void {
    $rows = [
        [
            'id' => 1,
            'name' => 'General',
            'slug' => 'general',
            'description' => null,
            'is_archived' => 0,
            'created_by' => 1,
            'created_at' => '2026-02-24 00:00:00',
            'updated_at' => '2026-02-24 00:00:00',
        ],
        [
            'id' => 2,
            'name' => 'Random',
            'slug' => 'random',
            'description' => null,
            'is_archived' => 0,
            'created_by' => 1,
            'created_at' => '2026-02-24 00:00:00',
            'updated_at' => '2026-02-24 00:00:00',
        ],
    ];

    $queryBuilderFactory = createSpaceMockQueryBuilderFactory(queryResult: $rows);
    $connection = createSpaceMockConnection(queryResult: []);
    $metadataFactory = new EntityMetadataFactory();
    $hydrator = new EntityHydrator();

    $repository = new SpaceRepository(
        connection: $connection,
        metadataFactory: $metadataFactory,
        hydrator: $hydrator,
        queryBuilderFactory: $queryBuilderFactory,
    );

    $spaces = $repository->findActive();

    expect($spaces)->toHaveCount(2)
        ->and($spaces[0])->toBeInstanceOf(Space::class)
        ->and($spaces[0]->name)->toBe('General')
        ->and($spaces[1]->name)->toBe('Random');
});

it('finds active spaces using query builder instead of raw SQL', function (): void {
    $rows = [
        [
            'id' => 1,
            'name' => 'General',
            'slug' => 'general',
            'description' => null,
            'is_archived' => 0,
            'created_by' => 1,
            'created_at' => '2026-02-24 00:00:00',
            'updated_at' => '2026-02-24 00:00:00',
        ],
    ];

    $whereCalls = [];
    $queryBuilderFactory = createSpaceMockQueryBuilderFactory(
        queryResult: $rows,
        whereCalls: $whereCalls,
    );

    $connection = createSpaceMockConnection(queryResult: []);
    $metadataFactory = new EntityMetadataFactory();
    $hydrator = new EntityHydrator();

    $repository = new SpaceRepository(
        connection: $connection,
        metadataFactory: $metadataFactory,
        hydrator: $hydrator,
        queryBuilderFactory: $queryBuilderFactory,
    );

    $spaces = $repository->findActive();

    expect($spaces)->toHaveCount(1)
        ->and($spaces[0])->toBeInstanceOf(Space::class)
        ->and($spaces[0]->name)->toBe('General')
        ->and($whereCalls)->toHaveCount(1)
        ->and($whereCalls[0]['column'])->toBe('is_archived')
        ->and($whereCalls[0]['operator'])->toBe('=')
        ->and($whereCalls[0]['value'])->toBeFalse();
});

it('saves a new space with name, slug, description, and created_by', function (): void {
    $queryHistory = [];
    $connection = createSpaceMockConnectionWithHistory(queryResult: [], queryHistory: $queryHistory);
    $metadataFactory = new EntityMetadataFactory();
    $hydrator = new EntityHydrator();

    $repository = new SpaceRepository(
        connection: $connection,
        metadataFactory: $metadataFactory,
        hydrator: $hydrator,
    );

    $space = new Space(
        id: null,
        name: 'General',
        slug: 'general',
        description: 'The general discussion space',
        isArchived: false,
        createdBy: 1,
        createdAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
        updatedAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );

    $repository->save(entity: $space);

    expect($queryHistory)->toHaveCount(1)
        ->and($queryHistory[0]['sql'])->toContain('INSERT INTO spaces')
        ->and($queryHistory[0]['sql'])->toContain('name')
        ->and($queryHistory[0]['sql'])->toContain('slug')
        ->and($queryHistory[0]['sql'])->toContain('description')
        ->and($queryHistory[0]['sql'])->toContain('created_by');
});

it('has module.php with SpaceRepositoryInterface binding', function (): void {
    $modulePath = dirname(path: __DIR__, levels: 2) . '/module.php';

    $bindings = require $modulePath;

    expect(file_exists(filename: $modulePath))->toBeTrue()
        ->and($bindings)->toBeArray()
        ->and($bindings)->toHaveKey('bindings')
        ->and($bindings['bindings'])->toHaveKey(SpaceRepositoryInterface::class)
        ->and($bindings['bindings'][SpaceRepositoryInterface::class])->toBe(SpaceRepository::class);
});

// Helper function to create a mock query builder factory

/**
 * @param array<array<string, mixed>> $queryResult
 * @param array<array{column: string, operator: string, value: mixed}>|null $whereCalls
 */
function createSpaceMockQueryBuilderFactory(
    array $queryResult = [],
    ?array &$whereCalls = null,
): QueryBuilderFactoryInterface {
    $whereCalls ??= [];

    return new class ($queryResult, $whereCalls) implements QueryBuilderFactoryInterface
    {
        /**
         * @param array<array<string, mixed>> $queryResult
         * @param array<array{column: string, operator: string, value: mixed}> $whereCalls
         */
        public function __construct(
            private array $queryResult,
            private array &$whereCalls,
        ) {}

        public function create(): QueryBuilderInterface
        {
            return new class ($this->queryResult, $this->whereCalls) implements QueryBuilderInterface
            {
                /**
                 * @param array<array<string, mixed>> $queryResult
                 * @param array<array{column: string, operator: string, value: mixed}> $whereCalls
                 */
                public function __construct(
                    private array $queryResult,
                    private array &$whereCalls,
                ) {}

                public function table(string $table): static { return $this; }

                public function select(string ...$columns): static { return $this; }

                public function where(
                    string $column,
                    string $operator,
                    mixed $value,
                ): static {
                    $this->whereCalls[] = [
                        'column' => $column,
                        'operator' => $operator,
                        'value' => $value,
                    ];

                    return $this;
                }

                public function whereIn(string $column, array $values): static { return $this; }

                public function whereNull(string $column): static { return $this; }

                public function whereNotNull(string $column): static { return $this; }

                public function orWhere(string $column, string $operator, mixed $value): static { return $this; }

                public function join(string $table, string $first, string $operator, string $second): static { return $this; }

                public function leftJoin(string $table, string $first, string $operator, string $second): static { return $this; }

                public function rightJoin(string $table, string $first, string $operator, string $second): static { return $this; }

                public function orderBy(string $column, string $direction = 'ASC'): static { return $this; }

                public function limit(int $limit): static { return $this; }

                public function offset(int $offset): static { return $this; }

                /**
                 * @return array<array<string, mixed>>
                 */
                public function get(): array { return $this->queryResult; }

                /**
                 * @return array<string, mixed>|null
                 */
                public function first(): ?array { return $this->queryResult[0] ?? null; }

                public function insert(array $data): int { return 1; }

                public function update(array $data): int { return 1; }

                public function delete(): int { return 1; }

                public function count(): int { return count(value: $this->queryResult); }

                public function raw(string $sql, array $bindings = []): array { return []; }
            };
        }
    };
}

// Helper function to create a mock connection

function createSpaceMockConnection(
    array $queryResult = [],
): ConnectionInterface {
    return createSpaceMockConnectionWithHistory(queryResult: $queryResult, queryHistory: $unused);
}

/**
 * @param array<array<string, mixed>> $queryResult
 * @param array<array{sql: string, bindings: array<mixed>}>|null $queryHistory
 */
function createSpaceMockConnectionWithHistory(
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
