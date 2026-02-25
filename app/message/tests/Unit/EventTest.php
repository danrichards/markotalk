<?php

declare(strict_types=1);

use App\Message\Event\MentionDetectedEvent;
use App\Message\Event\MessageCreatedEvent;
use App\Message\Event\MessageDeletedEvent;
use App\Message\Event\MessageUpdatedEvent;
use App\User\Event\UserRegisteredEvent;

it('creates MessageCreatedEvent with message property', function (): void {
    $message = new stdClass();
    $event = new MessageCreatedEvent(message: $message);

    expect($event->message)->toBe($message);
});

it('creates MessageUpdatedEvent with message property', function (): void {
    $message = new stdClass();
    $event = new MessageUpdatedEvent(message: $message);

    expect($event->message)->toBe($message);
});

it('creates MessageDeletedEvent with message property', function (): void {
    $message = new stdClass();
    $event = new MessageDeletedEvent(message: $message);

    expect($event->message)->toBe($message);
});

it('creates MentionDetectedEvent with message and username properties', function (): void {
    $message = new stdClass();
    $event = new MentionDetectedEvent(message: $message, username: 'johndoe');

    expect($event->message)->toBe($message)
        ->and($event->username)->toBe('johndoe');
});

it('allows stopping propagation on all event types', function (): void {
    $message = new stdClass();
    $user = new stdClass();

    $messageCreated = new MessageCreatedEvent(message: $message);
    $messageUpdated = new MessageUpdatedEvent(message: $message);
    $messageDeleted = new MessageDeletedEvent(message: $message);
    $mentionDetected = new MentionDetectedEvent(message: $message, username: 'johndoe');
    $userRegistered = new UserRegisteredEvent(user: $user);

    $messageCreated->stopPropagation();
    $messageUpdated->stopPropagation();
    $messageDeleted->stopPropagation();
    $mentionDetected->stopPropagation();
    $userRegistered->stopPropagation();

    expect($messageCreated->propagationStopped)->toBeTrue()
        ->and($messageUpdated->propagationStopped)->toBeTrue()
        ->and($messageDeleted->propagationStopped)->toBeTrue()
        ->and($mentionDetected->propagationStopped)->toBeTrue()
        ->and($userRegistered->propagationStopped)->toBeTrue();
});
