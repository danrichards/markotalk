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
use Marko\Pagination\CursorPaginator;

// Helper functions

function makePaginationMessageRow(
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

function makePaginationMockConnection(
    array $queryResult = [],
    ?array &$queryHistory = null,
): ConnectionInterface {
    $queryHistory ??= [];

    return new class ($queryResult, $queryHistory) implements ConnectionInterface {
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

        public function query(
            string $sql,
            array $bindings = [],
        ): array {
            $this->queryHistory[] = ['sql' => $sql, 'bindings' => $bindings];

            return $this->queryResult;
        }

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

function makePaginationMockDispatcher(): EventDispatcherInterface
{
    return new class () implements EventDispatcherInterface {
        public function dispatch(Event $event): void {}
    };
}

function makePaginationRepository(
    array $queryResult = [],
    ?array &$queryHistory = null,
): MessageRepository {
    $connection = makePaginationMockConnection(
        queryResult: $queryResult,
        queryHistory: $queryHistory,
    );

    return new MessageRepository(
        connection: $connection,
        metadataFactory: new EntityMetadataFactory(),
        hydrator: new EntityHydrator(),
        dispatcher: makePaginationMockDispatcher(),
    );
}

// Tests

it('returns the most recent messages when no cursor is provided', function (): void {
    $rows = [
        makePaginationMessageRow(id: 3, body: 'Third'),
        makePaginationMessageRow(id: 2, body: 'Second'),
        makePaginationMessageRow(id: 1, body: 'First'),
    ];

    $queryHistory = [];
    $repository = makePaginationRepository(queryResult: $rows, queryHistory: $queryHistory);

    $paginator = $repository->findPaginated(spaceId: 1, perPage: 50);

    expect($paginator)->toBeInstanceOf(CursorPaginator::class)
        ->and($queryHistory[0]['sql'])->toContain('ORDER BY id DESC')
        ->and($queryHistory[0]['sql'])->toContain('space_id = ?')
        ->and($queryHistory[0]['bindings'])->toContain(1);
});

it('returns older messages when a cursor is provided', function (): void {
    $rows = [
        makePaginationMessageRow(id: 2, body: 'Second'),
        makePaginationMessageRow(id: 1, body: 'First'),
    ];

    $queryHistory = [];
    $repository = makePaginationRepository(queryResult: $rows, queryHistory: $queryHistory);

    // Cursor pointing to message id 5 (load messages older than 5)
    $cursor = base64_encode(string: json_encode(value: ['id' => 5]));
    $paginator = $repository->findPaginated(spaceId: 1, perPage: 50, cursor: $cursor);

    expect($paginator)->toBeInstanceOf(CursorPaginator::class)
        ->and($queryHistory[0]['sql'])->toContain('id < ?')
        ->and($queryHistory[0]['bindings'])->toContain(5);
});

it('indicates has_more is true when more messages exist', function (): void {
    // perPage = 2, return 3 rows (perPage+1) to signal more pages
    $rows = [
        makePaginationMessageRow(id: 3, body: 'Third'),
        makePaginationMessageRow(id: 2, body: 'Second'),
        makePaginationMessageRow(id: 1, body: 'First'),
    ];

    $repository = makePaginationRepository(queryResult: $rows);

    $paginator = $repository->findPaginated(spaceId: 1, perPage: 2);

    expect($paginator->hasMorePages())->toBeTrue();
});

it('indicates has_more is false when no more messages exist', function (): void {
    // perPage = 2, only 2 rows returned — no extra row to signal more pages
    $rows = [
        makePaginationMessageRow(id: 2, body: 'Second'),
        makePaginationMessageRow(id: 1, body: 'First'),
    ];

    $repository = makePaginationRepository(queryResult: $rows);

    $paginator = $repository->findPaginated(spaceId: 1, perPage: 2);

    expect($paginator->hasMorePages())->toBeFalse();
});

it('returns messages in ascending order within each page', function (): void {
    // DB returns rows in descending order (newest first), repository must reverse to ascending
    $rows = [
        makePaginationMessageRow(id: 3, body: 'Third'),
        makePaginationMessageRow(id: 2, body: 'Second'),
        makePaginationMessageRow(id: 1, body: 'First'),
    ];

    $repository = makePaginationRepository(queryResult: $rows);

    $paginator = $repository->findPaginated(spaceId: 1, perPage: 50);
    $items = $paginator->items();

    expect($items)->toHaveCount(3)
        ->and($items[0])->toBeInstanceOf(Message::class)
        ->and($items[0]->id)->toBe(1)
        ->and($items[1]->id)->toBe(2)
        ->and($items[2]->id)->toBe(3);
});

it('encodes the next cursor as base64 for the client', function (): void {
    // perPage = 2, return 3 rows to trigger has_more
    $rows = [
        makePaginationMessageRow(id: 3, body: 'Third'),
        makePaginationMessageRow(id: 2, body: 'Second'),
        makePaginationMessageRow(id: 1, body: 'First'),
    ];

    $repository = makePaginationRepository(queryResult: $rows);

    $paginator = $repository->findPaginated(spaceId: 1, perPage: 2);
    $data = $paginator->toArray();

    // Next cursor must be a base64-encoded JSON string containing the id of the oldest item in page
    $decoded = json_decode(json: base64_decode(string: $data['links']['next']), associative: true);

    expect($data['links']['next'])->toBeString()
        ->and(base64_decode(string: $data['links']['next'], strict: true))->not->toBeFalse()
        ->and($decoded)->toBeArray()
        ->and($decoded)->toHaveKey('id')
        ->and($decoded['id'])->toBe(2);
});
