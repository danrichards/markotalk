<?php

declare(strict_types=1);

namespace App\User\Policy;

use App\User\Entity\User;
use App\User\Enum\UserRole;

class UserPolicy
{
    public function ban(
        User $user,
        User $targetUser,
    ): bool {
        return $user->role === UserRole::Admin;
    }

    public function updateRole(
        User $user,
        User $targetUser,
    ): bool {
        return $user->role === UserRole::Admin;
    }
}
