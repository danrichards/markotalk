<?php

declare(strict_types=1);

namespace App\Message\Policy;

use App\Message\Entity\Message;
use App\User\Entity\User;
use App\User\Enum\UserRole;

class MessagePolicy
{
    public function edit(
        User $user,
        Message $message,
    ): bool {
        return $user->id === $message->userId || $user->role === UserRole::Admin;
    }

    public function delete(
        User $user,
        Message $message,
    ): bool {
        return $user->id === $message->userId || $user->role === UserRole::Admin;
    }

    /** @noinspection PhpUnused */
    public function pin(
        User $user,
        Message $message,
    ): bool {
        return $user->role === UserRole::Admin;
    }
}
