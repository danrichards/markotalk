<?php

declare(strict_types=1);

use App\User\Event\UserRegisteredEvent;

it('creates UserRegisteredEvent with user property', function (): void {
    $user = new stdClass();
    $event = new UserRegisteredEvent(user: $user);

    expect($event->user)->toBe($user);
});
