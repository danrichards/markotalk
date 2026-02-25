<?php

declare(strict_types=1);

namespace App\Space\Policy;

use App\Space\Entity\Space;
use App\User\Entity\User;
use App\User\Enum\UserRole;

readonly class SpacePolicy
{
    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(
        User $user,
        Space $space,
    ): bool {
        return $user->role === UserRole::Admin;
    }

    public function archive(
        User $user,
        Space $space,
    ): bool {
        return $user->role === UserRole::Admin;
    }
}
