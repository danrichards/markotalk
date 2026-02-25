<?php

declare(strict_types=1);

namespace App\Message\Observer;

use App\Message\Entity\Message;
use App\Message\Event\MentionDetectedEvent;
use App\Notification\Notification\MentionNotification;
use App\Space\Entity\Space;
use App\Space\Repository\SpaceRepositoryInterface;
use App\User\Repository\UserRepositoryInterface;
use Marko\Core\Attributes\Observer;
use Marko\Notification\NotificationSender;

#[Observer(event: MentionDetectedEvent::class)]
readonly class MentionNotificationObserver
{
    public function __construct(
        private UserRepositoryInterface $users,
        private SpaceRepositoryInterface $spaces,
        private NotificationSender $sender,
        private UserRepositoryInterface $authorRepository,
    ) {}

    public function handle(MentionDetectedEvent $event): void
    {
        $message = $event->message;

        if (!$message instanceof Message) {
            return;
        }

        $mentioned = $this->users->findByUsername(username: $event->username);

        if ($mentioned === null) {
            return;
        }

        if ($mentioned->id === $message->userId) {
            return;
        }

        $space = $this->spaces->find(id: $message->spaceId);

        if (!$space instanceof Space) {
            return;
        }

        $author = $this->authorRepository->find(id: $message->userId);
        $authorUsername = $author !== null ? $author->username : '';

        $notification = new MentionNotification(
            messageId: $message->id ?? 0,
            spaceSlug: $space->slug,
            authorUsername: $authorUsername,
            messagePreview: substr(string: $message->body, offset: 0, length: 100),
        );

        $this->sender->send(
            notifiables: $mentioned,
            notification: $notification,
        );
    }
}
