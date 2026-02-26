<?php

declare(strict_types=1);

namespace App\User\Service;

use App\User\Entity\User;
use App\User\Repository\UserRepositoryInterface;
use DateTimeImmutable;

readonly class DatabasePresenceTracker implements PresenceTrackerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private int $presenceTimeout,
    ) {}

    public function updateLastSeen(User $user): void
    {
        $this->userRepository->updateLastSeen(
            user: $user,
            timestamp: new DateTimeImmutable(),
        );
    }

    public function markOffline(User $user): void
    {
        $user->lastSeenAt = null;
        $this->userRepository->clearLastSeen(user: $user);
    }

    public function isOnline(User $user): bool
    {
        if ($user->lastSeenAt === null) {
            return false;
        }

        $threshold = new DateTimeImmutable(datetime: '-' . $this->presenceTimeout . ' seconds');

        return $user->lastSeenAt > $threshold;
    }

    /**
     * @return User[]
     */
    public function getOnlineUsers(): array
    {
        $threshold = new DateTimeImmutable(datetime: '-' . $this->presenceTimeout . ' seconds');

        return array_values(array: array_filter(
            array: $this->userRepository->findAll(),
            callback: fn(User $user): bool => $user->lastSeenAt !== null && $user->lastSeenAt > $threshold,
        ));
    }
}
