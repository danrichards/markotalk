<?php

declare(strict_types=1);

namespace App\Notification\Notification;

use Marko\Mail\Message;
use Marko\Notification\Contracts\NotifiableInterface;
use Marko\Notification\Contracts\NotificationInterface;

readonly class WelcomeNotification implements NotificationInterface
{
    public function __construct(
        public string $username,
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
            'message' => 'Welcome to MarkoTalk!',
        ];
    }
}
