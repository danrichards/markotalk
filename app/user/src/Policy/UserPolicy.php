<?php

declare(strict_types=1);

namespace App\User\Policy;

use App\User\Entity\User;
use App\User\Enum\UserRole;

class UserPolicy
{
    public function ban(
        User $user,
    ): bool {
        return $user->role === UserRole::Admin;
    }

    /** @noinspection PhpUnused */
    public function updateRole(
        User $user,
    ): bool {
        return $user->role === UserRole::Admin;
    }
}
