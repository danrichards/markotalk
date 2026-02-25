<?php

declare(strict_types=1);

namespace App\Space\Repository;

use App\Space\Entity\Space;
use Marko\Database\Repository\RepositoryInterface;

interface SpaceRepositoryInterface extends RepositoryInterface
{
    /**
     * Find a space by its slug.
     */
    public function findBySlug(
        string $slug,
    ): ?Space;

    /**
     * Find all active (non-archived) spaces.
     *
     * @return array<Space>
     */
    public function findActive(): array;
}
