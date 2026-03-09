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
        $result = $this->findOneBy(criteria: ['userId' => $userId, 'spaceId' => $spaceId]);

        if ($result === null) {
            return null;
        }

        return $result;
    }

    /**
     * Find all memberships for a user.
     *
     * @return array<SpaceMembership>
     */
    public function findAllForUser(
        int $userId,
    ): array {
        $sql = sprintf(
            'SELECT * FROM %s WHERE user_id = ?',
            $this->metadata->tableName,
        );

        $rows = $this->connection->query(sql: $sql, bindings: [$userId]);

        return array_map(
            callback: fn (array $row) => $this->hydrator->hydrate(
                entityClass: static::ENTITY_CLASS,
                row: $row,
                metadata: $this->metadata,
            ),
            array: $rows,
        );
    }

    /**
     * Find all memberships for a space.
     *
     * @return array<SpaceMembership>
     */
    public function findAllForSpace(
        int $spaceId,
    ): array {
        $sql = sprintf(
            'SELECT * FROM %s WHERE space_id = ?',
            $this->metadata->tableName,
        );

        $rows = $this->connection->query(sql: $sql, bindings: [$spaceId]);

        return array_map(
            callback: fn (array $row) => $this->hydrator->hydrate(
                entityClass: static::ENTITY_CLASS,
                row: $row,
                metadata: $this->metadata,
            ),
            array: $rows,
        );
    }

    /**
     * Count messages newer than the user's last_read_message_id in a space.
     */
    public function countUnread(
        int $userId,
        int $spaceId,
    ): int {
        $membership = $this->findByUserAndSpace(userId: $userId, spaceId: $spaceId);

        if ($membership === null || $membership->lastReadMessageId === null) {
            return 0;
        }

        $sql = 'SELECT COUNT(*) as count FROM messages WHERE space_id = ? AND id > ?';

        $rows = $this->connection->query(
            sql: $sql,
            bindings: [$spaceId, $membership->lastReadMessageId],
        );

        return (int) ($rows[0]['count'] ?? 0);
    }

    /**
     * Update the last read message ID for a membership.
     */
    public function updateLastReadMessageId(
        SpaceMembership $membership,
        int $messageId,
    ): void {
        $membership->lastReadMessageId = $messageId;

        $sql = sprintf(
            'UPDATE %s SET last_read_message_id = ? WHERE id = ?',
            $this->metadata->tableName,
        );

        $this->connection->execute(sql: $sql, bindings: [$messageId, $membership->id]);
    }

    /**
     * Clear last_read_message_id for all memberships referencing a given message.
     */
    public function clearLastReadMessageId(
        int $messageId,
    ): void {
        $sql = sprintf(
            'UPDATE %s SET last_read_message_id = NULL WHERE last_read_message_id = ?',
            $this->metadata->tableName,
        );

        $this->connection->execute(sql: $sql, bindings: [$messageId]);
    }
}
