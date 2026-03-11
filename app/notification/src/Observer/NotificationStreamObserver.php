<?php

declare(strict_types=1);

namespace App\Notification\Observer;

use App\Message\Entity\Message;
use App\Message\Event\MessageCreatedEvent;
use App\Space\Entity\SpaceMembership;
use App\Space\Repository\SpaceMembershipRepositoryInterface;
use Marko\Core\Attributes\Observer;
use Marko\Cache\Contracts\CacheInterface;
use Marko\Cache\Exceptions\InvalidKeyException;

#[Observer(event: MessageCreatedEvent::class)]
readonly class NotificationStreamObserver
{
    public function __construct(
        private CacheInterface $cache,
        private SpaceMembershipRepositoryInterface $spaceMembershipRepository,
    ) {}

    /**
     * @throws InvalidKeyException
     */
    public function handle(
        MessageCreatedEvent $event,
    ): void {
        $message = $event->message;

        if (!$message instanceof Message) {
            return;
        }

        $spaceMembers = $this->spaceMembershipRepository->findAllForSpace(spaceId: $message->spaceId);

        foreach ($spaceMembers as $membership) {
            if (!$membership instanceof SpaceMembership) {
                continue;
            }

            if ($membership->userId === $message->userId) {
                continue;
            }

            $this->cache->set(
                key: "notification_pending_$membership->userId",
                value: true,
            );
        }
    }
}
