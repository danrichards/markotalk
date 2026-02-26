<?php

declare(strict_types=1);

namespace App\Message\Repository;

use App\Message\Entity\Message;
use DateTimeImmutable;
use Marko\Database\Repository\RepositoryInterface;
use Marko\Pagination\CursorPaginator;
use Marko\Pagination\PaginationException;

interface MessageRepositoryInterface extends RepositoryInterface
{
    /**
     * Find messages in a space ordered by id ascending.
     *
     * @return array<Message>
     */
    public function findBySpace(
        int $spaceId,
        int $limit = 50,
    ): array;

    /**
     * Find messages in a space after a given message id (for SSE polling).
     *
     * @return array<Message>
     */
    public function findBySpaceSince(
        int $spaceId,
        int $sinceId,
    ): array;

    /**
     * Find messages in a space edited after a given timestamp (for SSE polling).
     *
     * @return array<Message>
     */
    public function findEditedSince(
        int $spaceId,
        DateTimeImmutable $since,
    ): array;

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
    ): CursorPaginator;
}
