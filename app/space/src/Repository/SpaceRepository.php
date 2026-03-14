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

        if (!$result instanceof Space) {
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
        return $this->query()
            ->where(column: 'is_archived', operator: '=', value: false)
            ->getEntities();
    }
}
