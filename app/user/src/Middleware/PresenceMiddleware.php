<?php

declare(strict_types=1);

namespace App\User\Middleware;

use App\User\Entity\User;
use App\User\Service\PresenceTrackerInterface;
use Marko\Authentication\Contracts\GuardInterface;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\Routing\Middleware\MiddlewareInterface;

readonly class PresenceMiddleware implements MiddlewareInterface
{
    public function __construct(
        private GuardInterface $guard,
        private PresenceTrackerInterface $tracker,
    ) {}

    public function handle(
        Request $request,
        callable $next,
    ): Response {
        $user = $this->guard->user();

        if ($user instanceof User) {
            $this->tracker->updateLastSeen(user: $user);
        }

        return $next($request);
    }
}
