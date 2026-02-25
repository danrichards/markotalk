<?php

declare(strict_types=1);

namespace App\Message\Event;

use Marko\Core\Event\Event;

class MessageDeletedEvent extends Event
{
    public function __construct(
        public object $message,
    ) {}
}
