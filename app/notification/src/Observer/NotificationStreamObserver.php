<?php

declare(strict_types=1);

namespace App\Notification\Observer;

use App\Message\Entity\Message;
use App\Message\Event\MessageCreatedEvent;
use App\Space\Entity\SpaceMembership;
use App\Space\Repository\SpaceMembershipRepositoryInterface;
use Marko\Core\Attributes\Observer;
use Psr\SimpleCache\CacheInterface;

#[Observer(event: MessageCreatedEvent::class)]
readonly class NotificationStreamObserver
{
    public function __construct(
        private CacheInterface $cache,
        private SpaceMembershipRepositoryInterface $memberships,
    ) {}

    public function handle(MessageCreatedEvent $event): void
    {
        $message = $event->message;

        if (!$message instanceof Message) {
            return;
        }

        $spaceMembers = $this->memberships->findAllForSpace(spaceId: $message->spaceId);

        foreach ($spaceMembers as $membership) {
            if (!$membership instanceof SpaceMembership) {
                continue;
            }

            if ($membership->userId === $message->userId) {
                continue;
            }

            $this->cache->set(
                key: "notification_pending:{$membership->userId}",
                value: true,
            );
        }
    }
}
