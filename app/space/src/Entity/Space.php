<?php

declare(strict_types=1);

namespace App\Space\Entity;

use DateTimeImmutable;
use Marko\Database\Attributes\Column;
use Marko\Database\Attributes\Index;
use Marko\Database\Attributes\Table;
use Marko\Database\Entity\Entity;

#[Table(name: 'spaces')]
#[Index(name: 'idx_spaces_slug', columns: ['slug'], unique: true)]
#[Index(name: 'idx_spaces_name', columns: ['name'], unique: true)]
class Space extends Entity
{
    public function __construct(
        #[Column(primaryKey: true, autoIncrement: true)]
        public ?int $id,
        #[Column(name: 'name', length: 100, unique: true)]
        public string $name,
        #[Column(name: 'slug', length: 100, unique: true)]
        public string $slug,
        #[Column(name: 'description', type: 'TEXT')]
        public ?string $description,
        #[Column(name: 'is_archived', type: 'boolean', default: false)]
        public bool $isArchived,
        #[Column(name: 'created_by', references: 'users.id')]
        public int $createdBy,
        #[Column(name: 'created_at', type: 'datetime')]
        public DateTimeImmutable $createdAt,
        #[Column(name: 'updated_at', type: 'datetime')]
        public DateTimeImmutable $updatedAt,
    ) {}
}
