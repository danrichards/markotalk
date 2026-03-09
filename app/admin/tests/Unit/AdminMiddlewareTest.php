<?php

declare(strict_types=1);

use App\Admin\Middleware\AdminMiddleware;
use App\User\Entity\User;
use App\User\Enum\UserRole;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\Routing\Middleware\MiddlewareInterface;
use Marko\Testing\Fake\FakeGuard;

function makeAdminTestUser(UserRole $role = UserRole::User): User
{
    return new User(
        id: 1,
        username: 'johndoe',
        email: 'john@example.com',
        password: 'hashed_password',
        displayName: 'John Doe',
        avatarUrl: null,
        role: $role,
        isBanned: false,
        lastSeenAt: null,
        rememberToken: null,
        createdAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
        updatedAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );
}

it('allows admin users to proceed', function (): void {
    $guard = new FakeGuard();
    $guard->login(user: makeAdminTestUser(role: UserRole::Admin));

    $middleware = new AdminMiddleware(guard: $guard);
    $request = new Request();
    $expectedResponse = new Response(body: 'success', statusCode: 200);

    $response = $middleware->handle(
        request: $request,
        next: fn (Request $r) => $expectedResponse,
    );

    expect($response)->toBe($expectedResponse)
        ->and($response->statusCode())->toBe(200);
});

it('returns 403 for non-admin authenticated users', function (): void {
    $guard = new FakeGuard();
    $guard->login(user: makeAdminTestUser());

    $middleware = new AdminMiddleware(guard: $guard);
    $request = new Request();

    $response = $middleware->handle(
        request: $request,
        next: fn (Request $r) => new Response(body: 'success', statusCode: 200),
    );

    expect($response->statusCode())->toBe(403);
});

it('returns 401 for unauthenticated users', function (): void {
    $guard = new FakeGuard();

    $middleware = new AdminMiddleware(guard: $guard);
    $request = new Request();

    $response = $middleware->handle(
        request: $request,
        next: fn (Request $r) => new Response(body: 'success', statusCode: 200),
    );

    expect($response->statusCode())->toBe(401);
});

it('implements MiddlewareInterface', function (): void {
    $guard = new FakeGuard();
    $middleware = new AdminMiddleware(guard: $guard);

    expect($middleware)->toBeInstanceOf(MiddlewareInterface::class);
});
