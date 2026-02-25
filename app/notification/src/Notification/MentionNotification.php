<?php

declare(strict_types=1);

namespace App\Notification\Notification;

use Marko\Mail\Message;
use Marko\Notification\Contracts\NotifiableInterface;
use Marko\Notification\Contracts\NotificationInterface;

readonly class MentionNotification implements NotificationInterface
{
    public function __construct(
        public int $messageId,
        public string $spaceSlug,
        public string $authorUsername,
        public string $messagePreview,
    ) {}

    public function channels(NotifiableInterface $notifiable): array
    {
        return ['database'];
    }

    public function toMail(NotifiableInterface $notifiable): Message
    {
        return Message::create();
    }

    public function toDatabase(NotifiableInterface $notifiable): array
    {
        return [
            'message_id' => $this->messageId,
            'space_slug' => $this->spaceSlug,
            'author_username' => $this->authorUsername,
            'message_preview' => $this->messagePreview,
        ];
    }
}
