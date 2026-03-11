<?php

declare(strict_types=1);

namespace App\Message\Observer;

use App\Message\Entity\Message;
use App\Message\Event\MentionDetectedEvent;
use App\Notification\Notification\MentionNotification;
use App\Space\Entity\Space;
use App\Space\Repository\SpaceRepositoryInterface;
use App\User\Entity\User;
use App\User\Repository\UserRepositoryInterface;
use Marko\Core\Attributes\Observer;
use Marko\Notification\Exceptions\ChannelException;
use Marko\Notification\Exceptions\NotificationException;
use Marko\Notification\NotificationSender;

#[Observer(event: MentionDetectedEvent::class)]
readonly class MentionNotificationObserver
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private SpaceRepositoryInterface $spaceRepository,
        private NotificationSender $sender,
    ) {}

    /**
     * @throws NotificationException|ChannelException
     */
    public function handle(MentionDetectedEvent $event): void
    {
        $message = $event->message;

        if (!$message instanceof Message) {
            return;
        }

        $mentioned = $this->userRepository->findByUsername(username: $event->username);

        if (!$mentioned instanceof User) {
            return;
        }

        if ($mentioned->id === $message->userId) {
            return;
        }

        $space = $this->spaceRepository->find(id: $message->spaceId);

        if (!$space instanceof Space) {
            return;
        }

        $author = $this->userRepository->find(id: $message->userId);
        $authorUsername = $author instanceof User ? $author->username : '';
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
