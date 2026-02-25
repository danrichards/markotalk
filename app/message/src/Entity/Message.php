<?php

declare(strict_types=1);

namespace App\Message\Entity;

use DateTimeImmutable;
use Marko\Database\Attributes\Column;
use Marko\Database\Attributes\Index;
use Marko\Database\Attributes\Table;
use Marko\Database\Entity\Entity;

#[Table(name: 'messages')]
#[Index(name: 'idx_messages_space_id_id', columns: ['space_id', 'id'])]
class Message extends Entity
{
    public function __construct(
        #[Column(primaryKey: true, autoIncrement: true)]
        public ?int $id,
        #[Column(name: 'space_id', references: 'spaces.id')]
        public int $spaceId,
        #[Column(name: 'user_id', references: 'users.id')]
        public int $userId,
        #[Column(name: 'body', type: 'TEXT')]
        public string $body,
        #[Column(name: 'body_html', type: 'TEXT')]
        public string $bodyHtml,
        #[Column(name: 'is_pinned', type: 'boolean', default: false)]
        public bool $isPinned,
        #[Column(name: 'edited_at', type: 'datetime')]
        public ?DateTimeImmutable $editedAt,
        #[Column(name: 'created_at', type: 'datetime')]
        public DateTimeImmutable $createdAt,
    ) {}
}
