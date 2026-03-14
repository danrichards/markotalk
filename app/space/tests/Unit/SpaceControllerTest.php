<?php

declare(strict_types=1);

use App\Message\Repository\MessageRepositoryInterface;
use App\Space\Controller\SpaceController;
use App\Space\Entity\Space;
use App\Space\Entity\SpaceMembership;
use App\Space\Repository\SpaceMembershipRepositoryInterface;
use App\Space\Repository\SpaceRepositoryInterface;
use App\User\Entity\User;
use App\User\Repository\UserRepositoryInterface;
use App\User\Service\PresenceTrackerInterface;
use Marko\Authentication\AuthManager;
use Marko\Authentication\AuthenticatableInterface;
use Marko\Authentication\Middleware\AuthMiddleware;
use Marko\Database\Entity\Entity;
use Marko\Pagination\CursorPaginator;
use Marko\Routing\Attributes\Get;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\Security\Contracts\CsrfTokenManagerInterface;
use Marko\View\ViewInterface;

// ─── Helper factories ────────────────────────────────────────────────────────

function makeSpaceControllerView(): ViewInterface
{
    return new class implements ViewInterface {
        public string $lastTemplate = '';

        /** @var array<string, mixed> */
        public array $lastData = [];

        public function render(string $template, array $data = []): Response
        {
            $this->lastTemplate = $template;
            $this->lastData = $data;

            return new Response(body: '<html>space</html>', statusCode: 200);
        }

        public function renderToString(string $template, array $data = []): string
        {
            return '<html>space</html>';
        }
    };
}

function makeSpaceRepositoryStub(
    ?Space $bySlug = null,
    array $active = [],
): SpaceRepositoryInterface {
    return new class ($bySlug, $active) implements SpaceRepositoryInterface {
        public function __construct(
            private readonly ?Space $bySlug,
            private readonly array $active,
        ) {}

        public function findBySlug(string $slug): ?Space
        {
            return $this->bySlug;
        }

        public function findActive(): array
        {
            return $this->active;
        }

        public function find(int $id): ?Entity { return null; }

        public function findOrFail(int $id): Entity
        {
            throw new RuntimeException(message: 'Not implemented');
        }

        public function findAll(): array { return []; }

        public function findBy(array $criteria): array { return []; }

        public function findOneBy(array $criteria): ?Entity { return null; }

        public function existsBy(array $criteria): bool { return $this->findOneBy(criteria: $criteria) !== null; }

        public function clearLastReadMessageId(int $messageId): void {}

        public function save(Entity $entity): void {}

        public function delete(Entity $entity): void {}
    };
}

function makeSpaceMembershipRepositoryStub(
    ?SpaceMembership $byUserAndSpace = null,
    array $allForUser = [],
): SpaceMembershipRepositoryInterface {
    return new class ($byUserAndSpace, $allForUser) implements SpaceMembershipRepositoryInterface {
        public bool $saveCalled = false;

        public bool $deleteCalled = false;

        public function __construct(
            private readonly ?SpaceMembership $byUserAndSpace,
            private readonly array $allForUser,
        ) {}

        public function findByUserAndSpace(int $userId, int $spaceId): ?SpaceMembership
        {
            return $this->byUserAndSpace;
        }

        public function findAllForUser(int $userId): array
        {
            return $this->allForUser;
        }

        public function findAllForSpace(int $spaceId): array { return []; }

        public function countUnread(int $userId, int $spaceId): int { return 0; }

        public function updateLastReadMessageId(SpaceMembership $membership, int $messageId): void {}

        public function find(int $id): ?Entity { return null; }

        public function findOrFail(int $id): Entity
        {
            throw new RuntimeException(message: 'Not implemented');
        }

        public function findAll(): array { return []; }

        public function findBy(array $criteria): array { return []; }

        public function findOneBy(array $criteria): ?Entity { return null; }

        public function existsBy(array $criteria): bool { return $this->findOneBy(criteria: $criteria) !== null; }

        public function clearLastReadMessageId(int $messageId): void {}

        public function save(Entity $entity): void
        {
            $this->saveCalled = true;
        }

        public function delete(Entity $entity): void
        {
            $this->deleteCalled = true;
        }
    };
}

function makeAuthUser(int $id = 1): AuthenticatableInterface
{
    return new class ($id) implements AuthenticatableInterface {
        public function __construct(private readonly int $id) {}

        public function getAuthIdentifier(): int|string { return $this->id; }

        public function getAuthIdentifierName(): string { return 'id'; }

        public function getAuthPassword(): string { return 'hashed'; }

        public function getRememberToken(): ?string { return null; }

        public function setRememberToken(?string $token): void {}

        public function getRememberTokenName(): string { return 'remember_token'; }
    };
}

function makeSpaceControllerAuthManager(?AuthenticatableInterface $user = null): AuthManager
{
    /** @noinspection PhpMissingParentConstructorInspection - Test stub intentionally skips parent */
    return new class ($user) extends AuthManager {
        /** @noinspection PhpMissingParentConstructorInspection */
        public function __construct(
            private readonly ?AuthenticatableInterface $mockUser,
        ) {}

        public function user(): ?AuthenticatableInterface
        {
            return $this->mockUser;
        }
    };
}

function makeMessageRepositoryStub(): MessageRepositoryInterface
{
    return new class implements MessageRepositoryInterface {
        public function findPinnedBySpace(int $spaceId): array { return []; }

        public function findBySpace(int $spaceId, int $limit = 50): array { return []; }

        public function findBySpaceSince(int $spaceId, int $sinceId): array { return []; }

        public function findEditedSince(int $spaceId, \DateTimeImmutable $since): array { return []; }

        public function findPaginated(int $spaceId, int $perPage = 50, ?string $cursor = null): CursorPaginator
        {
            return new CursorPaginator(items: [], perPage: $perPage);
        }

        public function find(int $id): ?Entity { return null; }

        public function findOrFail(int $id): Entity
        {
            throw new RuntimeException(message: 'Not implemented');
        }

        public function findAll(): array { return []; }

        public function findBy(array $criteria): array { return []; }

        public function findOneBy(array $criteria): ?Entity { return null; }

        public function existsBy(array $criteria): bool { return $this->findOneBy(criteria: $criteria) !== null; }

        public function save(Entity $entity): void {}

        public function delete(Entity $entity): void {}
    };
}

function makeSpaceUserRepositoryStub(): UserRepositoryInterface
{
    return new class implements UserRepositoryInterface {
        public function findByEmail(string $email): ?User { return null; }

        public function findByUsername(string $username): ?User { return null; }

        public function findByRememberToken(int $userId, string $token): ?User { return null; }

        public function updateRememberToken(User $user, ?string $token): void {}

        public function updateLastSeen(User $user, DateTimeImmutable $timestamp): void {}

        public function clearLastSeen(User $user): void {}

        public function find(int $id): ?Entity { return null; }

        public function findOrFail(int $id): Entity
        {
            throw new RuntimeException(message: 'Not implemented');
        }

        public function findAll(): array { return []; }

        public function findBy(array $criteria): array { return []; }

        public function findOneBy(array $criteria): ?Entity { return null; }

        public function existsBy(array $criteria): bool { return $this->findOneBy(criteria: $criteria) !== null; }

        public function save(Entity $entity): void {}

        public function delete(Entity $entity): void {}
    };
}

function makeSpaceCsrfTokenManagerStub(): CsrfTokenManagerInterface
{
    return new class implements CsrfTokenManagerInterface {
        public function get(): string { return 'test-token'; }

        public function validate(string $token): bool { return true; }

        public function regenerate(): string { return 'test-token'; }
    };
}

function makeSpacePresenceTrackerStub(): PresenceTrackerInterface
{
    return new class implements PresenceTrackerInterface {
        public function updateLastSeen(User $user): void {}

        public function markOffline(User $user): void {}

        public function isOnline(User $user): bool { return false; }

        public function getOnlineUsers(): array { return []; }
    };
}

function makeSpaceController(
    SpaceRepositoryInterface $spaces,
    SpaceMembershipRepositoryInterface $memberships,
    ViewInterface $view,
    AuthManager $auth,
): SpaceController {
    return new SpaceController(
        spaceRepository: $spaces,
        spaceMembershipRepository: $memberships,
        messageRepository: makeMessageRepositoryStub(),
        userRepository: makeSpaceUserRepositoryStub(),
        view: $view,
        auth: $auth,
        csrf: makeSpaceCsrfTokenManagerStub(),
        presence: makeSpacePresenceTrackerStub(),
    );
}

function makeSpace(int $id = 1, string $slug = 'general'): Space
{
    return new Space(
        id: $id,
        name: 'General',
        slug: $slug,
        description: null,
        isArchived: false,
        createdBy: 1,
        createdAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
        updatedAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );
}

function makeMembership(int $spaceId = 1): SpaceMembership
{
    return new SpaceMembership(
        id: 1,
        userId: 1,
        spaceId: $spaceId,
        lastReadMessageId: null,
        joinedAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );
}

function makeSpaceGetRequest(string $uri = '/'): Request
{
    return new Request(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => $uri]);
}

function makeSpacePostRequest(string $uri = '/spaces/general/join'): Request
{
    return new Request(server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => $uri]);
}

// ─── Tests ───────────────────────────────────────────────────────────────────

it('redirects GET / to the first joined space', function (): void {
    $user = makeAuthUser();
    $space = makeSpace(id: 2);
    $membership = makeMembership(spaceId: 2);

    $spaces = makeSpaceRepositoryStub(active: [$space]);
    $memberships = makeSpaceMembershipRepositoryStub(allForUser: [$membership]);
    $view = makeSpaceControllerView();
    $auth = makeSpaceControllerAuthManager(user: $user);

    $controller = makeSpaceController(spaces: $spaces, memberships: $memberships, view: $view, auth: $auth);
    $response = $controller->index();

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(302)
        ->and($response->headers()['Location'])->toBe('/spaces/general');
});

it('shows the chat view for GET /spaces/{slug}', function (): void {
    $user = makeAuthUser();
    $space = makeSpace();
    $membership = makeMembership();

    $spaces = makeSpaceRepositoryStub(bySlug: $space);
    $memberships = makeSpaceMembershipRepositoryStub(byUserAndSpace: $membership);
    $view = makeSpaceControllerView();
    $auth = makeSpaceControllerAuthManager(user: $user);

    $controller = makeSpaceController(spaces: $spaces, memberships: $memberships, view: $view, auth: $auth);
    $response = $controller->show(slug: 'general');

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(200)
        ->and($view->lastTemplate)->toBe('space::space/show')
        ->and($view->lastData['space'])->toBe($space);
});

it('returns 404 for a non-existent space slug', function (): void {
    $spaces = makeSpaceRepositoryStub();
    $memberships = makeSpaceMembershipRepositoryStub();
    $view = makeSpaceControllerView();
    $auth = makeSpaceControllerAuthManager(user: makeAuthUser());

    $controller = makeSpaceController(spaces: $spaces, memberships: $memberships, view: $view, auth: $auth);
    $response = $controller->show(slug: 'nonexistent');

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(404);
});

it('joins a space on POST /spaces/{slug}/join', function (): void {
    $user = makeAuthUser();
    $space = makeSpace();

    $spaces = makeSpaceRepositoryStub(bySlug: $space);
    $memberships = makeSpaceMembershipRepositoryStub();
    $view = makeSpaceControllerView();
    $auth = makeSpaceControllerAuthManager(user: $user);

    $controller = makeSpaceController(spaces: $spaces, memberships: $memberships, view: $view, auth: $auth);
    $response = $controller->join(slug: 'general');

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(302)
        ->and($response->headers()['Location'])->toBe('/spaces/general')
        ->and($memberships->saveCalled)->toBeTrue();
});

it('leaves a space on POST /spaces/{slug}/leave', function (): void {
    $user = makeAuthUser();
    $space = makeSpace();
    $membership = makeMembership();

    $spaces = makeSpaceRepositoryStub(bySlug: $space);
    $memberships = makeSpaceMembershipRepositoryStub(byUserAndSpace: $membership);
    $view = makeSpaceControllerView();
    $auth = makeSpaceControllerAuthManager(user: $user);

    $controller = makeSpaceController(spaces: $spaces, memberships: $memberships, view: $view, auth: $auth);
    $response = $controller->leave(slug: 'general');

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(302)
        ->and($response->headers()['Location'])->toBe('/home')
        ->and($memberships->deleteCalled)->toBeTrue();
});

it('no longer routes GET / to SpaceController::index', function (): void {
    $reflection = new ReflectionClass(objectOrClass: SpaceController::class);
    $method = $reflection->getMethod(name: 'index');
    $attributes = $method->getAttributes();
    $routePath = null;

    foreach ($attributes as $attribute) {
        $instance = $attribute->newInstance();
        if ($instance instanceof Get) {
            $routePath = $instance->path;
        }
    }

    expect($routePath)->not->toBe('/');
});

it('redirects to /home after leaving a space', function (): void {
    $user = makeAuthUser();
    $space = makeSpace();
    $membership = makeMembership();

    $spaces = makeSpaceRepositoryStub(bySlug: $space);
    $memberships = makeSpaceMembershipRepositoryStub(byUserAndSpace: $membership);
    $view = makeSpaceControllerView();
    $auth = makeSpaceControllerAuthManager(user: $user);

    $controller = makeSpaceController(spaces: $spaces, memberships: $memberships, view: $view, auth: $auth);
    $response = $controller->leave(slug: 'general');

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(302)
        ->and($response->headers()['Location'])->toBe('/home');
});

it('routes GET /home with AuthMiddleware to SpaceController::index', function (): void {
    $reflection = new ReflectionClass(objectOrClass: SpaceController::class);
    $method = $reflection->getMethod(name: 'index');
    $attributes = $method->getAttributes();
    $routePath = null;
    $hasAuthMiddleware = false;

    foreach ($attributes as $attribute) {
        $instance = $attribute->newInstance();
        if ($instance instanceof Get) {
            $routePath = $instance->path;
        }
        if (property_exists(object_or_class: $instance, property: 'middleware')) {
            $middleware = $instance->middleware;
            if (in_array(needle: AuthMiddleware::class, haystack: $middleware, strict: true)) {
                $hasAuthMiddleware = true;
            }
        }
    }

    expect($routePath)->toBe('/home')
        ->and($hasAuthMiddleware)->toBeTrue();
});

it('requires authentication on all routes', function (): void {
    $reflection = new ReflectionClass(objectOrClass: SpaceController::class);
    $methods = ['index', 'show', 'join', 'leave'];

    foreach ($methods as $methodName) {
        $method = $reflection->getMethod(name: $methodName);
        $routeAttributes = $method->getAttributes();
        $hasAuthMiddleware = false;

        foreach ($routeAttributes as $attribute) {
            $instance = $attribute->newInstance();
            if (property_exists(object_or_class: $instance, property: 'middleware')) {
                $middleware = $instance->middleware;
                if (in_array(needle: AuthMiddleware::class, haystack: $middleware, strict: true)) {
                    $hasAuthMiddleware = true;
                    break;
                }
            }
        }

        expect($hasAuthMiddleware)->toBeTrue("Method {$methodName} must have AuthMiddleware");
    }
});
