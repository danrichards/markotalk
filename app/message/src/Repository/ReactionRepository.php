<?php

declare(strict_types=1);

namespace App\Message\Repository;

use App\Message\Entity\Reaction;
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
        return $this->query()
            ->where(column: 'message_id', operator: '=', value: $messageId)
            ->getEntities();
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
        $sql = 'SELECT emoji, COUNT(*) as count, MAX(CASE WHEN user_id = ? THEN 1 ELSE 0 END) as user_reacted
                 FROM ' . $this->metadata->tableName . ' WHERE message_id = ? GROUP BY emoji';

        $rows = $this->query()->raw(sql: $sql, bindings: [$userId, $messageId]);

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
        $this->query()
            ->where(column: 'message_id', operator: '=', value: $messageId)
            ->where(column: 'user_id', operator: '=', value: $userId)
            ->where(column: 'emoji', operator: '=', value: $emoji)
            ->delete();
    }
}
