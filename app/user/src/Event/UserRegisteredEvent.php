<?php

declare(strict_types=1);

namespace App\User\Event;

use Marko\Core\Event\Event;

class UserRegisteredEvent extends Event
{
    public function __construct(
        public object $user,
    ) {}
}
