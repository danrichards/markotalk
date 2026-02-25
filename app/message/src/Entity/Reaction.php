<?php

declare(strict_types=1);

namespace App\Message\Entity;

use Marko\Database\Attributes\Column;
use Marko\Database\Attributes\Index;
use Marko\Database\Attributes\Table;
use Marko\Database\Entity\Entity;

#[Table(name: 'reactions')]
#[Index(name: 'uniq_reactions_message_user_emoji', columns: ['message_id', 'user_id', 'emoji'], unique: true)]
class Reaction extends Entity
{
    public function __construct(
        #[Column(primaryKey: true, autoIncrement: true)]
        public ?int $id,
        #[Column(name: 'message_id', references: 'messages.id')]
        public int $messageId,
        #[Column(name: 'user_id', references: 'users.id')]
        public int $userId,
        #[Column(name: 'emoji', length: 50)]
        public string $emoji,
    ) {}
}
