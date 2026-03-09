<?php

declare(strict_types=1);

namespace App\Message\Repository;

use App\Message\Entity\Reaction;
use Marko\Database\Entity\Entity;
use Marko\Database\Repository\Repository;

/**
 * @extends Repository<Reaction>
 */
class ReactionRepository extends Repository implements ReactionRepositoryInterface
{
    protected const string ENTITY_CLASS = Reaction::class;

    /**
     * Find all reactions for a message.
     *
     * @return array<Reaction>
     */
    public function findByMessage(int $messageId): array
    {
        $sql = sprintf(
            'SELECT * FROM %s WHERE message_id = ?',
            $this->metadata->tableName,
        );

        $rows = $this->connection->query(sql: $sql, bindings: [$messageId]);

        return array_map(
            callback: fn (array $row): Entity => $this->hydrator->hydrate(
                entityClass: static::ENTITY_CLASS,
                row: $row,
                metadata: $this->metadata,
            ),
            array: $rows,
        );
    }

    /**
     * Find reactions grouped by emoji with counts and whether the given user reacted.
     *
     * @return array<array{emoji: string, count: int, user_reacted: bool}>
     */
    public function findGrouped(
        int $messageId,
        int $userId,
    ): array {
        $sql = sprintf(
            'SELECT emoji, COUNT(*) as count, MAX(CASE WHEN user_id = ? THEN 1 ELSE 0 END) as user_reacted
             FROM %s WHERE message_id = ? GROUP BY emoji',
            $this->metadata->tableName,
        );

        $rows = $this->connection->query(sql: $sql, bindings: [$userId, $messageId]);

        return array_map(
            callback: fn (array $row): array => [
                'emoji' => (string) $row['emoji'],
                'count' => (int) $row['count'],
                'user_reacted' => (bool) $row['user_reacted'],
            ],
            array: $rows,
        );
    }

    /**
     * Add a new reaction.
     */
    public function add(
        int $messageId,
        int $userId,
        string $emoji,
    ): void {
        $reaction = new Reaction(
            id: null,
            messageId: $messageId,
            userId: $userId,
            emoji: $emoji,
        );

        $this->save(entity: $reaction);
    }

    /**
     * Remove an existing reaction.
     */
    public function remove(
        int $messageId,
        int $userId,
        string $emoji,
    ): void {
        $sql = sprintf(
            'DELETE FROM %s WHERE message_id = ? AND user_id = ? AND emoji = ?',
            $this->metadata->tableName,
        );

        $this->connection->execute(sql: $sql, bindings: [$messageId, $userId, $emoji]);
    }
}
