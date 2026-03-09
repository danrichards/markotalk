<?php

declare(strict_types=1);

namespace App\Space\Repository;

use App\Space\Entity\SpaceMembership;
use Marko\Database\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<SpaceMembership>
 */
interface SpaceMembershipRepositoryInterface extends RepositoryInterface
{
    /**
     * Find a membership by user and space.
     */
    public function findByUserAndSpace(
        int $userId,
        int $spaceId,
    ): ?SpaceMembership;

    /**
     * Find all memberships for a user.
     *
     * @return array<SpaceMembership>
     */
    public function findAllForUser(
        int $userId,
    ): array;

    /**
     * Find all memberships for a space.
     *
     * @return array<SpaceMembership>
     */
    public function findAllForSpace(
        int $spaceId,
    ): array;

    /**
     * Count messages newer than the user's last_read_message_id in a space.
     */
    public function countUnread(
        int $userId,
        int $spaceId,
    ): int;

    /**
     * Update the last read message ID for a membership.
     */
    public function updateLastReadMessageId(
        SpaceMembership $membership,
        int $messageId,
    ): void;

    /**
     * Clear last_read_message_id for all memberships referencing a given message.
     */
    public function clearLastReadMessageId(
        int $messageId,
    ): void;
}
