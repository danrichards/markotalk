<?php

declare(strict_types=1);

namespace App\Message\Repository;

use App\Message\Entity\Message;
use App\Message\Event\MessageCreatedEvent;
use DateTimeImmutable;
use Marko\Database\Entity\Entity;
use Marko\Database\Exceptions\RepositoryException;
use Marko\Database\Repository\Repository;
use Marko\Pagination\Cursor;
use Marko\Pagination\CursorPaginator;
use Marko\Pagination\Exceptions\PaginationException;

/**
 * @extends Repository<Message>
 */
class MessageRepository extends Repository implements MessageRepositoryInterface
{
    protected const string ENTITY_CLASS = Message::class;

    /**
     * Save a message and dispatch MessageCreatedEvent for new messages.
     *
     * @throws RepositoryException
     */
    public function save(
        Entity $entity,
    ): void {
        $isNew = $this->hydrator->isNew(entity: $entity, metadata: $this->metadata);

        parent::save(entity: $entity);

        if ($isNew) {
            $this->eventDispatcher?->dispatch(event: new MessageCreatedEvent(message: $entity));
        }
    }

    /**
     * Find pinned messages in a space ordered by id ascending.
     *
     * @return array<Message>
     */
    public function findPinnedBySpace(
        int $spaceId,
    ): array {
        return $this->query()
            ->where(column: 'space_id', operator: '=', value: $spaceId)
            ->where(column: 'is_pinned', operator: '=', value: true)
            ->orderBy(column: 'id', direction: 'ASC')
            ->getEntities();
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
        return $this->query()
            ->where(column: 'space_id', operator: '=', value: $spaceId)
            ->orderBy(column: 'id', direction: 'ASC')
            ->limit(limit: $limit)
            ->getEntities();
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

        $query = $this->query()
            ->where(column: 'space_id', operator: '=', value: $spaceId)
            ->orderBy(column: 'id', direction: 'DESC')
            ->limit(limit: $perPage + 1);

        if ($cursorId !== null) {
            $query->where(column: 'id', operator: '<', value: $cursorId);
        }

        $items = $query->getEntities();

        $hasMore = count(value: $items) > $perPage;

        if ($hasMore) {
            array_pop(array: $items);
        }

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
        return $this->query()
            ->where(column: 'space_id', operator: '=', value: $spaceId)
            ->where(column: 'id', operator: '>', value: $sinceId)
            ->orderBy(column: 'id', direction: 'ASC')
            ->getEntities();
    }

    /**
     * Find messages in a space edited after a given timestamp.
     *
     * @return array<Message>
     */
    public function findEditedSince(
        int $spaceId,
        DateTimeImmutable $since,
    ): array {
        return $this->query()
            ->where(column: 'space_id', operator: '=', value: $spaceId)
            ->where(column: 'edited_at', operator: '>', value: $since->format('Y-m-d H:i:s'))
            ->orderBy(column: 'id', direction: 'ASC')
            ->getEntities();
    }
}
