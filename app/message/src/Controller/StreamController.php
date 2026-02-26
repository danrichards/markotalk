<?php

declare(strict_types=1);

namespace App\Message\Controller;

use App\Message\Entity\Message;
use App\Message\Repository\MessageRepositoryInterface;
use App\Space\Repository\SpaceRepositoryInterface;
use App\User\Entity\User;
use App\User\Repository\UserRepositoryInterface;
use App\User\Service\PresenceTrackerInterface;
use DateTimeImmutable;
use Marko\Authentication\AuthManager;
use Marko\Authentication\Middleware\AuthMiddleware;
use Marko\Config\ConfigRepositoryInterface;
use Marko\Routing\Attributes\Get;
use Marko\Routing\Http\Request;
use Marko\Security\Contracts\CsrfTokenManagerInterface;
use Marko\Sse\SseEvent;
use Marko\Sse\SseStream;
use Marko\Sse\StreamingResponse;

readonly class StreamController
{
    public function __construct(
        private SpaceRepositoryInterface $spaces,
        private MessageRepositoryInterface $messages,
        private UserRepositoryInterface $users,
        private PresenceTrackerInterface $presence,
        private ConfigRepositoryInterface $config,
        private AuthManager $auth,
        private CsrfTokenManagerInterface $csrf,
    ) {}

    #[Get('/spaces/{slug}/stream', middleware: [AuthMiddleware::class])]
    public function stream(
        string $slug,
        Request $request,
    ): StreamingResponse {
        $pollInterval = (int) $this->config->get(key: 'markotalk.sse_poll_interval');
        $heartbeatInterval = (int) $this->config->get(key: 'markotalk.sse_heartbeat_interval');
        $timeout = (int) $this->config->get(key: 'markotalk.sse_timeout');

        $space = $this->spaces->findBySlug(slug: $slug);
        $lastEventId = (int) ($request->header(name: 'Last-Event-ID') ?? 0);

        $currentUserId = (int) $this->auth->id();
        $csrfToken = $this->csrf->get();
        $lastEditCheck = new DateTimeImmutable();

        $dataProvider = function () use ($space, &$lastEventId, &$lastEditCheck, $currentUserId, $csrfToken): array {
            if ($space === null) {
                return [];
            }

            $newMessages = $this->messages->findBySpaceSince(
                spaceId: $space->id,
                sinceId: $lastEventId,
            );

            $events = array_map(
                callback: fn (Message $message): SseEvent => new SseEvent(
                    data: $this->renderMessageHtml(
                        message: $message,
                        currentUserId: $currentUserId,
                        csrfToken: $csrfToken,
                    ),
                    event: 'message',
                    id: $message->id,
                ),
                array: $newMessages,
            );

            if (count(value: $newMessages) > 0) {
                $lastMessage = end(array: $newMessages);
                $lastEventId = $lastMessage->id ?? $lastEventId;
            }

            // Check for edited messages since last poll
            $newMessageIds = array_map(
                callback: fn (Message $m): int => (int) $m->id,
                array: $newMessages,
            );
            $editedMessages = $this->messages->findEditedSince(
                spaceId: (int) $space->id,
                since: $lastEditCheck,
            );
            $lastEditCheck = new DateTimeImmutable();

            foreach ($editedMessages as $edited) {
                // Skip messages already sent as new
                if (in_array((int) $edited->id, $newMessageIds, true)) {
                    continue;
                }
                $events[] = new SseEvent(
                    data: ['id' => $edited->id, 'bodyHtml' => $edited->bodyHtml],
                    event: 'message_edited',
                );
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

    private function renderMessageHtml(
        Message $message,
        int $currentUserId,
        string $csrfToken,
    ): string {
        $user = $this->users->find(id: $message->userId);
        $authorName = htmlspecialchars(
            string: $user !== null ? ($user->displayName ?: $user->username) : 'Unknown User',
        );
        $timestamp = $message->createdAt->format('M j, g:i A');
        $pinnedClass = $message->isPinned ? ' message-pinned' : '';
        $pinnedBadge = $message->isPinned ? '<span class="message-pin-badge">Pinned</span>' : '';

        $actionsHtml = '';
        if ($message->userId === $currentUserId) {
            $actionsHtml = <<<HTML
              <div class="message-actions">
                <button class="message-action-edit" data-message-id="{$message->id}">Edit</button>
                <button class="message-action-delete" data-message-id="{$message->id}" data-csrf-token="{$csrfToken}">Delete</button>
              </div>
            HTML;
        }

        return <<<HTML
        <div class="message{$pinnedClass}" data-message-id="{$message->id}">
          <div class="message-header">
            <div class="message-avatar"></div>
            <span class="message-author">{$authorName}</span>
            <span class="message-timestamp">{$timestamp}</span>
            {$pinnedBadge}
          </div>
          <div class="message-body">{$message->bodyHtml}</div>
          {$actionsHtml}
        </div>
        HTML;
    }
}
