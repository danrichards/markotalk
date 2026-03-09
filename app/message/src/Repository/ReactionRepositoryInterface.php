<?php

declare(strict_types=1);

namespace App\Message\Repository;

use App\Message\Entity\Reaction;
use Marko\Database\Repository\RepositoryInterface;

/**
 * @extends RepositoryInterface<Reaction>
 */
interface ReactionRepositoryInterface extends RepositoryInterface
{
    /**
     * Find all reactions for a message.
     *
     * @return array<Reaction>
     */
    public function findByMessage(
        int $messageId,
    ): array;

    /**
     * Find reactions grouped by emoji with counts and whether the given user reacted.
     *
     * @return array<array{emoji: string, count: int, user_reacted: bool}>
     */
    public function findGrouped(
        int $messageId,
        int $userId,
    ): array;

    /**
     * Add a new reaction.
     */
    public function add(
        int $messageId,
        int $userId,
        string $emoji,
    ): void;

    /**
     * Remove an existing reaction.
     */
    public function remove(
        int $messageId,
        int $userId,
        string $emoji,
    ): void;
}
