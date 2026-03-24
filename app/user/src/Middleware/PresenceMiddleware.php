<?php

declare(strict_types=1);

namespace App\User\Middleware;

use App\User\Entity\User;
use App\User\Service\PresenceTrackerInterface;
use Marko\Authentication\Contracts\GuardInterface;
use Marko\PubSub\Message;
use Marko\PubSub\PublisherInterface;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\Routing\Middleware\MiddlewareInterface;

readonly class PresenceMiddleware implements MiddlewareInterface
{
    public function __construct(
        private GuardInterface $guard,
        private PresenceTrackerInterface $tracker,
        private ?PublisherInterface $publisher = null,
    ) {}

    public function handle(
        Request $request,
        callable $next,
    ): Response {
        $user = $this->guard->user();

        if ($user instanceof User) {
            $this->tracker->updateLastSeen(user: $user);
            $this->publishPresenceIfSpaceRequest(request: $request);
        }

        return $next($request);
    }

    private function publishPresenceIfSpaceRequest(Request $request): void
    {
        if ($this->publisher === null) {
            return;
        }

        $uri = $request->path();

        if (preg_match(pattern: '/^\/spaces\/([a-z0-9-]+)/', subject: $uri, matches: $matches) !== 1) {
            return;
        }

        $slug = $matches[1];
        $channel = 'space:' . $slug;

        $onlineUsers = $this->tracker->getOnlineUsers();
        $onlineIds = array_map(callback: fn(User $u): int => $u->id, array: $onlineUsers);
        $onlineNames = [];
        foreach ($onlineUsers as $u) {
            $onlineNames[$u->id] = $u->displayName ?: $u->username;
        }

        $payload = json_encode(value: ['type' => 'presence', 'onlineIds' => $onlineIds, 'onlineNames' => $onlineNames]);

        $this->publisher->publish(
            channel: $channel,
            message: new Message(channel: $channel, payload: $payload),
        );
    }
}
