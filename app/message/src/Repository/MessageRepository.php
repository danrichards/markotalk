<?php

declare(strict_types=1);

namespace App\Message\Repository;

use App\Message\Entity\Message;
use App\Message\Event\MessageCreatedEvent;
use Closure;
use Marko\Core\Event\EventDispatcherInterface;
use Marko\Database\Connection\ConnectionInterface;
use Marko\Database\Entity\Entity;
use Marko\Database\Entity\EntityHydrator;
use Marko\Database\Entity\EntityMetadataFactory;
use Marko\Database\Repository\Repository;
use Marko\Pagination\Cursor;
use Marko\Pagination\CursorPaginator;
use Marko\Pagination\PaginationException;

/**
 * @extends Repository<Message>
 */
class MessageRepository extends Repository implements MessageRepositoryInterface
{
    protected const string ENTITY_CLASS = Message::class;

    public function __construct(
        ConnectionInterface $connection,
        EntityMetadataFactory $metadataFactory,
        EntityHydrator $hydrator,
        private readonly EventDispatcherInterface $dispatcher,
        ?Closure $queryBuilderFactory = null,
    ) {
        parent::__construct(
            connection: $connection,
            metadataFactory: $metadataFactory,
            hydrator: $hydrator,
            queryBuilderFactory: $queryBuilderFactory,
        );
    }

    /**
     * Save a message and dispatch MessageCreatedEvent for new messages.
     */
    public function save(
        Entity $entity,
    ): void {
        $isNew = $this->hydrator->isNew(entity: $entity, metadata: $this->metadata);

        parent::save(entity: $entity);

        if ($isNew) {
            $this->dispatcher->dispatch(event: new MessageCreatedEvent(message: $entity));
        }
    }

    /**
     * Find messages in a space ordered by id ascending.
     *
     * @return array<Message>
     */
    public function findBySpace(
        int $spaceId,
        int $limit = 50,
    ): array {
        $sql = sprintf(
            'SELECT * FROM %s WHERE space_id = ? ORDER BY id ASC LIMIT ?',
            $this->metadata->tableName,
        );

        $rows = $this->connection->query(sql: $sql, bindings: [$spaceId, $limit]);

        return array_map(
            callback: fn (array $row): Message => $this->hydrator->hydrate(
                entityClass: static::ENTITY_CLASS,
                row: $row,
                metadata: $this->metadata,
            ),
            array: $rows,
        );
    }

    /**
     * Find paginated messages in a space using cursor-based pagination.
     *
     * Returns the most recent messages when no cursor is provided, or older
     * messages when a cursor is provided. Messages within a page are ordered
     * ascending (oldest first) for display.
     *
     * @throws PaginationException
     */
    public function findPaginated(
        int $spaceId,
        int $perPage = 50,
        ?string $cursor = null,
    ): CursorPaginator {
        $cursorId = null;

        if ($cursor !== null) {
            $decoded = Cursor::decode(encoded: $cursor);
            $cursorId = (int) $decoded->parameter(name: 'id');
        }

        if ($cursorId !== null) {
            $sql = sprintf(
                'SELECT * FROM %s WHERE space_id = ? AND id < ? ORDER BY id DESC LIMIT ?',
                $this->metadata->tableName,
            );
            $bindings = [$spaceId, $cursorId, $perPage + 1];
        } else {
            $sql = sprintf(
                'SELECT * FROM %s WHERE space_id = ? ORDER BY id DESC LIMIT ?',
                $this->metadata->tableName,
            );
            $bindings = [$spaceId, $perPage + 1];
        }

        $rows = $this->connection->query(sql: $sql, bindings: $bindings);

        $hasMore = count(value: $rows) > $perPage;

        if ($hasMore) {
            array_pop(array: $rows);
        }

        $items = array_map(
            callback: fn (array $row): Message => $this->hydrator->hydrate(
                entityClass: static::ENTITY_CLASS,
                row: $row,
                metadata: $this->metadata,
            ),
            array: $rows,
        );

        // Reverse to ascending order for display
        $items = array_reverse(array: $items);

        $nextCursor = null;

        if ($hasMore && count(value: $items) > 0) {
            $oldestItem = $items[0];
            $nextCursor = new Cursor(params: ['id' => $oldestItem->id]);
        }

        return new CursorPaginator(
            items: $items,
            perPage: $perPage,
            nextCursor: $nextCursor,
        );
    }

    /**
     * Find messages in a space after a given message id (for SSE polling).
     *
     * @return array<Message>
     */
    public function findBySpaceSince(
        int $spaceId,
        int $sinceId,
    ): array {
        $sql = sprintf(
            'SELECT * FROM %s WHERE space_id = ? AND id > ? ORDER BY id ASC',
            $this->metadata->tableName,
        );

        $rows = $this->connection->query(sql: $sql, bindings: [$spaceId, $sinceId]);

        return array_map(
            callback: fn (array $row): Message => $this->hydrator->hydrate(
                entityClass: static::ENTITY_CLASS,
                row: $row,
                metadata: $this->metadata,
            ),
            array: $rows,
        );
    }

    /**
     * Find messages in a space edited after a given timestamp.
     *
     * @return array<Message>
     */
    public function findEditedSince(
        int $spaceId,
        \DateTimeImmutable $since,
    ): array {
        $sql = sprintf(
            'SELECT * FROM %s WHERE space_id = ? AND edited_at > ? ORDER BY id ASC',
            $this->metadata->tableName,
        );

        $rows = $this->connection->query(
            sql: $sql,
            bindings: [$spaceId, $since->format('Y-m-d H:i:s')],
        );

        return array_map(
            callback: fn (array $row): Message => $this->hydrator->hydrate(
                entityClass: static::ENTITY_CLASS,
                row: $row,
                metadata: $this->metadata,
            ),
            array: $rows,
        );
    }
}
