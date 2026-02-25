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
    $migrationFile = dirname(__DIR__, 4) . '/database/migrations/20260224000004_create_messages.php';

    expect(file_exists($migrationFile))->toBeTrue();

    $content = file_get_contents($migrationFile);

    expect($content)
        ->toContain('CREATE TABLE messages')
        ->toContain('space_id')
        ->toContain('user_id')
        ->toContain('body')
        ->toContain('body_html')
        ->toContain('is_pinned')
        ->toContain('edited_at')
        ->toContain('created_at')
        ->toContain('PRIMARY KEY')
        ->toContain('idx_messages_space_id_id')
        ->toContain('DROP TABLE messages');
});

it('finds messages by space ordered by id ascending', function (): void {
    $messageRows = [
        createMessageRow(id: 1, spaceId: 1, body: 'First'),
        createMessageRow(id: 2, spaceId: 1, body: 'Second'),
    ];

    $queryHistory = [];
    $connection = createMessageMockConnectionWithHistory($messageRows, $queryHistory);
    $dispatcher = createMockDispatcher();

    $repository = new MessageRepository(
        connection: $connection,
        metadataFactory: new EntityMetadataFactory(),
        hydrator: new EntityHydrator(),
        dispatcher: $dispatcher,
    );

    $messages = $repository->findBySpace(spaceId: 1);

    expect($messages)->toHaveCount(2)
        ->and($messages[0])->toBeInstanceOf(Message::class)
        ->and($messages[0]->body)->toBe('First')
        ->and($queryHistory[0]['sql'])->toContain('space_id = ?')
        ->and($queryHistory[0]['sql'])->toContain('ORDER BY id ASC')
        ->and($queryHistory[0]['bindings'])->toContain(1);
});

it('finds messages by space since a given message id', function (): void {
    $messageRows = [
        createMessageRow(id: 5, spaceId: 1, body: 'After message 3'),
    ];

    $queryHistory = [];
    $connection = createMessageMockConnectionWithHistory($messageRows, $queryHistory);
    $dispatcher = createMockDispatcher();

    $repository = new MessageRepository(
        connection: $connection,
        metadataFactory: new EntityMetadataFactory(),
        hydrator: new EntityHydrator(),
        dispatcher: $dispatcher,
    );

    $messages = $repository->findBySpaceSince(spaceId: 1, sinceId: 3);

    expect($messages)->toHaveCount(1)
        ->and($messages[0])->toBeInstanceOf(Message::class)
        ->and($messages[0]->body)->toBe('After message 3')
        ->and($queryHistory[0]['sql'])->toContain('space_id = ?')
        ->and($queryHistory[0]['sql'])->toContain('id > ?')
        ->and($queryHistory[0]['sql'])->toContain('ORDER BY id ASC')
        ->and($queryHistory[0]['bindings'])->toContain(1)
        ->and($queryHistory[0]['bindings'])->toContain(3);
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
        dispatcher: $dispatcher,
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

    $repository->save($message);

    expect($queryHistory)->toHaveCount(1)
        ->and($queryHistory[0]['sql'])->toContain('INSERT INTO messages')
        ->and($dispatchedEvents)->toHaveCount(1)
        ->and($dispatchedEvents[0])->toBeInstanceOf(\App\Message\Event\MessageCreatedEvent::class)
        ->and($dispatchedEvents[0]->message)->toBe($message);
});

it('has module.php with MessageRepositoryInterface binding', function (): void {
    $modulePath = dirname(__DIR__, 2) . '/module.php';

    expect(file_exists($modulePath))->toBeTrue();

    $bindings = require $modulePath;

    expect($bindings)->toBeArray()
        ->and($bindings)->toHaveKey('bindings')
        ->and($bindings['bindings'])->toHaveKey(MessageRepositoryInterface::class)
        ->and($bindings['bindings'][MessageRepositoryInterface::class])->toBe(MessageRepository::class);
});

// Helper functions

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
            throw new RuntimeException('Not implemented');
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
