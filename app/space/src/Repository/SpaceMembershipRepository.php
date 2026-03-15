<?php

declare(strict_types=1);

namespace App\Space\Repository;

use App\Space\Entity\SpaceMembership;
use Marko\Database\Repository\Repository;

/**
 * @extends Repository<SpaceMembership>
 */
class SpaceMembershipRepository extends Repository implements SpaceMembershipRepositoryInterface
{
    protected const string ENTITY_CLASS = SpaceMembership::class;

    /**
     * Find a membership by user and space.
     */
    public function findByUserAndSpace(
        int $userId,
        int $spaceId,
    ): ?SpaceMembership {
        return $this->findOneBy(criteria: [
            'userId' => $userId,
            'spaceId' => $spaceId,
        ]);
    }

    /**
     * Find all memberships for a user.
     *
     * @return array<SpaceMembership>
     */
    public function findAllForUser(
        int $userId,
    ): array {
        return $this->query()
            ->where(column: 'user_id', operator: '=', value: $userId)
            ->getEntities();
    }

    /**
     * Find all memberships for a space.
     *
     * @return array<SpaceMembership>
     */
    public function findAllForSpace(
        int $spaceId,
    ): array {
        return $this->query()
            ->where(column: 'space_id', operator: '=', value: $spaceId)
            ->getEntities();
    }

    /**
     * Count messages newer than the user's last_read_message_id in a space.
     */
    public function countUnread(
        int $userId,
        int $spaceId,
    ): int {
        $membership = $this->findByUserAndSpace(userId: $userId, spaceId: $spaceId);

        if (!$membership instanceof SpaceMembership || $membership->lastReadMessageId === null) {
            return 0;
        }

        return $this->queryBuilderFactory->create()
            ->table(table: 'messages')
            ->where(column: 'space_id', operator: '=', value: $spaceId)
            ->where(column: 'id', operator: '>', value: $membership->lastReadMessageId)
            ->count();
    }

    /**
     * Update the last read message ID for a membership.
     */
    public function updateLastReadMessageId(
        SpaceMembership $membership,
        int $messageId,
    ): void {
        $membership->lastReadMessageId = $messageId;
        $this->save(entity: $membership);
    }

    /**
     * Clear last_read_message_id for all memberships referencing a given message.
     */
    public function clearLastReadMessageId(
        int $messageId,
    ): void {
        $this->query()
            ->where(column: 'last_read_message_id', operator: '=', value: $messageId)
            ->update(data: ['last_read_message_id' => null]);
    }
}
