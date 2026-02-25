<?php

declare(strict_types=1);

namespace App\Admin\Middleware;

use App\User\Entity\User;
use App\User\Enum\UserRole;
use Marko\Authentication\Contracts\GuardInterface;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\Routing\Middleware\MiddlewareInterface;

readonly class AdminMiddleware implements MiddlewareInterface
{
    public function __construct(
        private GuardInterface $guard,
    ) {}

    public function handle(
        Request $request,
        callable $next,
    ): Response {
        if (!$this->guard->check()) {
            return new Response(
                body: 'Unauthorized',
                statusCode: 401,
            );
        }

        $user = $this->guard->user();

        if (!$user instanceof User || $user->role !== UserRole::Admin) {
            return new Response(
                body: 'Forbidden',
                statusCode: 403,
            );
        }

        return $next($request);
    }
}
