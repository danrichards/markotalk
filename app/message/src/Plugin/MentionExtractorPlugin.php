<?php

declare(strict_types=1);

namespace App\Message\Plugin;

use App\Message\Entity\Message;
use App\Message\Event\MentionDetectedEvent;
use App\Message\Repository\MessageRepository;
use Marko\Core\Attributes\After;
use Marko\Core\Attributes\Plugin;
use Marko\Core\Event\EventDispatcherInterface;

#[Plugin(target: MessageRepository::class)]
readonly class MentionExtractorPlugin
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {}

    #[After(sortOrder: 10)]
    public function afterSave(
        mixed $result,
        Message $message,
    ): mixed {
        preg_match_all(
            pattern: '/@([a-zA-Z0-9_]+)/',
            subject: $message->body,
            matches: $matches,
        );

        $usernames = array_unique(array: $matches[1]);

        foreach ($usernames as $username) {
            $this->dispatcher->dispatch(new MentionDetectedEvent(
                message: $message,
                username: $username,
            ));
        }

        return $result;
    }
}
