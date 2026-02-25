<?php

declare(strict_types=1);

namespace App\Message\Controller;

use App\Message\Entity\Message;
use App\Message\Repository\MessageRepositoryInterface;
use App\Space\Repository\SpaceRepositoryInterface;
use App\User\Entity\User;
use App\User\Service\PresenceTrackerInterface;
use Marko\Authentication\Middleware\AuthMiddleware;
use Marko\Routing\Attributes\Get;
use Marko\Routing\Http\Request;
use Marko\Sse\SseEvent;
use Marko\Sse\SseStream;
use Marko\Sse\StreamingResponse;

readonly class StreamController
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private SpaceRepositoryInterface $spaces,
        private MessageRepositoryInterface $messages,
        private PresenceTrackerInterface $presence,
        private array $config,
    ) {}

    #[Get('/spaces/{slug}/stream', middleware: [AuthMiddleware::class])]
    public function stream(
        string $slug,
        Request $request,
    ): StreamingResponse {
        $pollInterval = (int) ($this->config['sse_poll_interval'] ?? 1);
        $heartbeatInterval = (int) ($this->config['sse_heartbeat_interval'] ?? 15);
        $timeout = (int) ($this->config['sse_timeout'] ?? 300);

        $space = $this->spaces->findBySlug(slug: $slug);
        $lastEventId = (int) ($request->header(name: 'Last-Event-ID') ?? 0);

        $dataProvider = function () use ($space, &$lastEventId): array {
            if ($space === null) {
                return [];
            }

            $newMessages = $this->messages->findBySpaceSince(
                spaceId: $space->id,
                sinceId: $lastEventId,
            );

            $events = array_map(
                callback: fn (Message $message): SseEvent => new SseEvent(
                    data: $message->bodyHtml,
                    event: 'message',
                    id: $message->id,
                ),
                array: $newMessages,
            );

            if (count(value: $newMessages) > 0) {
                $lastMessage = end(array: $newMessages);
                $lastEventId = $lastMessage->id ?? $lastEventId;
            }

            $onlineUsers = $this->presence->getOnlineUsers();
            $onlineIds = array_map(
                callback: fn (User $user): int => $user->id,
                array: $onlineUsers,
            );

            $events[] = new SseEvent(
                data: $onlineIds,
                event: 'presence',
            );

            return $events;
        };

        return new StreamingResponse(
            stream: new SseStream(
                dataProvider: $dataProvider,
                heartbeatInterval: $heartbeatInterval,
                timeout: $timeout,
                pollInterval: $pollInterval,
            ),
        );
    }
}
