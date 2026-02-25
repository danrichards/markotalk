<?php

declare(strict_types=1);

namespace App\Space\Entity;

use DateTimeImmutable;
use Marko\Database\Attributes\Column;
use Marko\Database\Attributes\Index;
use Marko\Database\Attributes\Table;
use Marko\Database\Entity\Entity;

#[Table(name: 'space_memberships')]
#[Index(name: 'idx_space_memberships_user_space', columns: ['user_id', 'space_id'], unique: true)]
class SpaceMembership extends Entity
{
    public function __construct(
        #[Column(primaryKey: true, autoIncrement: true)]
        public ?int $id,
        #[Column(name: 'user_id', references: 'users.id')]
        public int $userId,
        #[Column(name: 'space_id', references: 'spaces.id')]
        public int $spaceId,
        #[Column(name: 'last_read_message_id', references: 'messages.id')]
        public ?int $lastReadMessageId,
        #[Column(name: 'joined_at', type: 'datetime')]
        public DateTimeImmutable $joinedAt,
    ) {}
}
