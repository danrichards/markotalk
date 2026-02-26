<?php

declare(strict_types=1);

use App\Message\Entity\Reaction;
use App\Message\Repository\ReactionRepository;
use App\Message\Repository\ReactionRepositoryInterface;
use Marko\Database\Connection\ConnectionInterface;
use Marko\Database\Connection\StatementInterface;
use Marko\Database\Entity\EntityHydrator;
use Marko\Database\Entity\EntityMetadataFactory;

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

    expect(file_exists($migrationFile))->toBeTrue();

    $content = file_get_contents($migrationFile);

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

    $repository = new ReactionRepository(
        connection: $connection,
        metadataFactory: new EntityMetadataFactory(),
        hydrator: new EntityHydrator(),
    );

    $reactions = $repository->findByMessage(messageId: 5);

    expect($reactions)->toHaveCount(2)
        ->and($reactions[0])->toBeInstanceOf(Reaction::class)
        ->and($reactions[0]->emoji)->toBe('👍')
        ->and($queryHistory[0]['sql'])->toContain('message_id = ?')
        ->and($queryHistory[0]['bindings'])->toContain(5);
});

it('groups reactions by emoji with counts', function (): void {
    $groupedRows = [
        ['emoji' => '👍', 'count' => 3, 'user_reacted' => 1],
        ['emoji' => '❤️', 'count' => 1, 'user_reacted' => 0],
    ];

    $queryHistory = [];
    $connection = createReactionMockConnectionWithHistory($groupedRows, $queryHistory);

    $repository = new ReactionRepository(
        connection: $connection,
        metadataFactory: new EntityMetadataFactory(),
        hydrator: new EntityHydrator(),
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

    $repository = new ReactionRepository(
        connection: $connection,
        metadataFactory: new EntityMetadataFactory(),
        hydrator: new EntityHydrator(),
    );

    $repository->remove(messageId: 5, userId: 1, emoji: '👍');

    expect($queryHistory)->toHaveCount(1)
        ->and($queryHistory[0]['sql'])->toContain('DELETE FROM reactions')
        ->and($queryHistory[0]['sql'])->toContain('message_id = ?')
        ->and($queryHistory[0]['sql'])->toContain('user_id = ?')
        ->and($queryHistory[0]['sql'])->toContain('emoji = ?')
        ->and($queryHistory[0]['bindings'])->toContain(5)
        ->and($queryHistory[0]['bindings'])->toContain(1)
        ->and($queryHistory[0]['bindings'])->toContain('👍');
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
            throw new RuntimeException('Not implemented');
        }

        public function lastInsertId(): int
        {
            return 1;
        }
    };
}
