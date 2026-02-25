<?php

declare(strict_types=1);

use App\Notification\Notification\MentionNotification;
use App\Notification\Notification\WelcomeNotification;
use Marko\Notification\Contracts\NotificationInterface;
use Marko\Notification\Contracts\NotifiableInterface;

function makeNotifiable(): NotifiableInterface
{
    return new class implements NotifiableInterface {
        public function routeNotificationFor(string $channel): mixed
        {
            return match ($channel) {
                'mail' => 'user@example.com',
                'database' => ['type' => 'user', 'id' => 1],
                default => null,
            };
        }

        public function getNotifiableId(): string|int
        {
            return 1;
        }

        public function getNotifiableType(): string
        {
            return 'user';
        }
    };
}

function makeMentionNotification(): MentionNotification
{
    return new MentionNotification(
        messageId: 42,
        spaceSlug: 'general',
        authorUsername: 'johndoe',
        messagePreview: 'Hey @janedoe check this out',
    );
}

it('creates MentionNotification with database channel', function (): void {
    $notifiable = makeNotifiable();
    $notification = makeMentionNotification();

    expect($notification)->toBeInstanceOf(NotificationInterface::class)
        ->and($notification->channels(notifiable: $notifiable))->toBe(['database']);
});

it('formats MentionNotification for database with message details', function (): void {
    $notifiable = makeNotifiable();
    $notification = makeMentionNotification();

    expect($notification->toDatabase(notifiable: $notifiable))->toBe([
        'message_id' => 42,
        'space_slug' => 'general',
        'author_username' => 'johndoe',
        'message_preview' => 'Hey @janedoe check this out',
    ]);
});

it('creates WelcomeNotification with database channel', function (): void {
    $notifiable = makeNotifiable();
    $notification = new WelcomeNotification(username: 'janedoe');

    expect($notification)->toBeInstanceOf(NotificationInterface::class)
        ->and($notification->channels(notifiable: $notifiable))->toBe(['database']);
});

it('formats WelcomeNotification for database with welcome message', function (): void {
    $notifiable = makeNotifiable();
    $notification = new WelcomeNotification(username: 'janedoe');

    expect($notification->toDatabase(notifiable: $notifiable))->toBe([
        'message' => 'Welcome to MarkoTalk!',
    ]);
});

it('carries the relevant context (message, user) for each notification type', function (): void {
    $mention = makeMentionNotification();
    $welcome = new WelcomeNotification(username: 'janedoe');

    expect($mention->messageId)->toBe(42)
        ->and($mention->spaceSlug)->toBe('general')
        ->and($mention->authorUsername)->toBe('johndoe')
        ->and($mention->messagePreview)->toBe('Hey @janedoe check this out')
        ->and($welcome->username)->toBe('janedoe');
});
