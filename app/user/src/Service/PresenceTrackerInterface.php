<?php

declare(strict_types=1);

namespace App\User\Service;

use App\User\Entity\User;

interface PresenceTrackerInterface
{
    public function updateLastSeen(User $user): void;

    public function markOffline(User $user): void;

    public function isOnline(User $user): bool;

    /**
     * @return User[]
     */
    public function getOnlineUsers(): array;
}
