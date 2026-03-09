<?php

declare(strict_types=1);

namespace App\Space\Repository;

use App\Space\Entity\Space;
use Marko\Database\Repository\Repository;

/**
 * @extends Repository<Space>
 */
class SpaceRepository extends Repository implements SpaceRepositoryInterface
{
    protected const string ENTITY_CLASS = Space::class;

    /**
     * Find a space by its slug.
     */
    public function findBySlug(
        string $slug,
    ): ?Space {
        $result = $this->findOneBy(criteria: ['slug' => $slug]);

        if ($result === null) {
            return null;
        }

        return $result;
    }

    /**
     * Find all active (non-archived) spaces.
     *
     * @return array<Space>
     */
    public function findActive(): array
    {
        $sql = sprintf(
            'SELECT * FROM %s WHERE is_archived = ?',
            $this->metadata->tableName,
        );

        $rows = $this->connection->query(sql: $sql, bindings: [0]);

        return array_map(
            callback: fn (array $row) => $this->hydrator->hydrate(
                entityClass: static::ENTITY_CLASS,
                row: $row,
                metadata: $this->metadata,
            ),
            array: $rows,
        );
    }
}
