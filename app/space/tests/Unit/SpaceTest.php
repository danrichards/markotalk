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
use Marko\Database\Repository\Repository;
use Marko\Database\Repository\RepositoryInterface;

it('has a migration that creates the spaces table with all columns and indexes', function (): void {
    $migrationFile = dirname(path: __DIR__, levels: 4) . '/database/migrations/20260225191740_create_spaces.php';

    $content = file_get_contents($migrationFile);

    expect(file_exists($migrationFile))->toBeTrue()
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
    $queryHistory = [];
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

    $connection = createSpaceMockConnectionWithHistory(queryResult: $rows, queryHistory: $queryHistory);
    $metadataFactory = new EntityMetadataFactory();
    $hydrator = new EntityHydrator();

    $repository = new SpaceRepository(
        connection: $connection,
        metadataFactory: $metadataFactory,
        hydrator: $hydrator,
    );

    $spaces = $repository->findActive();

    expect($spaces)->toHaveCount(2)
        ->and($spaces[0])->toBeInstanceOf(Space::class)
        ->and($spaces[0]->name)->toBe('General')
        ->and($queryHistory[0]['sql'])->toContain('is_archived = ?')
        ->and($queryHistory[0]['bindings'])->toContain(0);
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

    expect(file_exists($modulePath))->toBeTrue()
        ->and($bindings)->toBeArray()
        ->and($bindings)->toHaveKey('bindings')
        ->and($bindings['bindings'])->toHaveKey(SpaceRepositoryInterface::class)
        ->and($bindings['bindings'][SpaceRepositoryInterface::class])->toBe(SpaceRepository::class);
});

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
