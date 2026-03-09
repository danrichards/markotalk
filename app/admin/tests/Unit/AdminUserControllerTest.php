<?php

declare(strict_types=1);

namespace App\Admin\Tests\Unit;

use App\Admin\Controller\AdminUserController;
use App\Admin\Middleware\AdminMiddleware;
use App\User\Entity\User;
use App\User\Enum\UserRole;
use App\User\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Marko\Authentication\AuthManager;
use Marko\Authentication\AuthenticatableInterface;
use Marko\Database\Entity\Entity;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\View\ViewInterface;
use ReflectionClass;
use RuntimeException;

// ─── Stub classes ────────────────────────────────────────────────────────────

class AdminUserControllerTest implements ViewInterface
{
    public string $lastTemplate = '';

    /** @var array<string, mixed> */
    public array $lastData = [];

    public function render(
        string $template,
        array $data = [],
    ): Response {
        $this->lastTemplate = $template;
        $this->lastData = $data;

        return Response::html(html: '<html lang="">admin users</html>');
    }

    public function renderToString(
        string $template,
        array $data = [],
    ): string {
        $this->lastTemplate = $template;
        $this->lastData = $data;

        return '<html lang="">admin users</html>';
    }
}

class AdminUserStubRepository implements UserRepositoryInterface
{
    public ?User $savedUser = null;

    public function __construct(
        /** @var User[] */
        private readonly array $allUsers = [],
        private readonly ?User $findResult = null,
    ) {
    }

    public function findByEmail(string $email): ?User {
        return null;
    }

    public function findByUsername(string $username): ?User {
        return null;
    }

    public function findByRememberToken(
        int $userId,
        string $token,
    ): ?User {
        return null;
    }

    public function updateRememberToken(
        User $user,
        ?string $token,
    ): void {
    }

    public function updateLastSeen(User $user, DateTimeImmutable $timestamp): void {
    }

    public function clearLastSeen(User $user): void {
    }

    public function find(int $id): ?Entity {
        return $this->findResult;
    }

    public function findOrFail(int $id): Entity {
        if ($this->findResult === null) {
            throw new RuntimeException(message: 'User not found');
        }

        return $this->findResult;
    }

    public function findAll(): array {
        return $this->allUsers;
    }

    public function findBy(array $criteria): array {
        return [];
    }

    public function findOneBy(array $criteria): ?Entity {
        return null;
    }

    public function existsBy(array $criteria): bool {
        return $this->findOneBy(criteria: $criteria) !== null;
    }

    public function save(Entity $entity): void {
        $this->savedUser = $entity instanceof User ? $entity : null;
    }

    public function delete(Entity $entity): void {
    }
}

// ─── Helper factories ─────────────────────────────────────────────────────────

function makeAdminUser(
    int $id = 1,
    UserRole $role = UserRole::Admin,
): User {
    return new User(
        id: $id,
        username: 'admin',
        email: 'admin@example.com',
        password: 'hashed_password',
        displayName: 'Admin User',
        avatarUrl: null,
        role: $role,
        isBanned: false,
        lastSeenAt: null,
        rememberToken: null,
        createdAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
        updatedAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );
}

function makeRegularUser(int $id = 2): User {
    return new User(
        id: $id,
        username: 'johndoe',
        email: 'john@example.com',
        password: 'hashed_password',
        displayName: 'John Doe',
        avatarUrl: null,
        role: UserRole::User,
        isBanned: false,
        lastSeenAt: null,
        rememberToken: null,
        createdAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
        updatedAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );
}

function makeAdminUserControllerAuthManager(?AuthenticatableInterface $user = null): AuthManager {
    return new class (mockUser: $user) extends AuthManager
        {
            public function __construct(
                private readonly ?AuthenticatableInterface $mockUser,
            ) {
                // Skip parent constructor
            }

            public function user(): ?AuthenticatableInterface {
                return $this->mockUser;
            }
        };
}

function makeAdminUserController(
    ?AdminUserStubRepository $repository = null,
    ?AdminUserControllerTest $view = null,
    ?AuthManager $auth = null,
): AdminUserController {
    return new AdminUserController(
        userRepository: $repository ?? new AdminUserStubRepository(),
        view: $view ?? new AdminUserControllerTest(),
        auth: $auth ?? makeAdminUserControllerAuthManager(),
    );
}

// ─── Tests ────────────────────────────────────────────────────────────────────

it('lists all users for admins', function (): void {
    $admin = makeAdminUser();
    $user = makeRegularUser();
    $repository = new AdminUserStubRepository(allUsers: [$admin, $user]);
    $view = new AdminUserControllerTest();
    $auth = makeAdminUserControllerAuthManager(user: $admin);
    $controller = makeAdminUserController(repository: $repository, view: $view, auth: $auth);

    $response = $controller->index();

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(200)
        ->and($view->lastTemplate)->toBe('admin::users/index')
        ->and($view->lastData['users'])->toBe([$admin, $user]);
});

it('bans a user', function (): void {
    $admin = makeAdminUser();
    $user = makeRegularUser();
    $repository = new AdminUserStubRepository(allUsers: [$admin, $user], findResult: $user);
    $auth = makeAdminUserControllerAuthManager(user: $admin);
    $controller = makeAdminUserController(repository: $repository, auth: $auth);

    $response = $controller->ban(id: '2');

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(302)
        ->and($response->headers()['Location'])->toBe('/admin/users')
        ->and($repository->savedUser)->not->toBeNull()
        ->and($repository->savedUser->isBanned)->toBeTrue();
});

it('unbans a user', function (): void {
    $admin = makeAdminUser();
    $bannedUser = new User(
        id: 2,
        username: 'banned',
        email: 'banned@example.com',
        password: 'hashed_password',
        displayName: 'Banned User',
        avatarUrl: null,
        role: UserRole::User,
        isBanned: true,
        lastSeenAt: null,
        rememberToken: null,
        createdAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
        updatedAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );
    $repository = new AdminUserStubRepository(findResult: $bannedUser);
    $auth = makeAdminUserControllerAuthManager(user: $admin);
    $controller = makeAdminUserController(repository: $repository, auth: $auth);

    $response = $controller->unban(id: '2');

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(302)
        ->and($response->headers()['Location'])->toBe('/admin/users')
        ->and($repository->savedUser)->not->toBeNull()
        ->and($repository->savedUser->isBanned)->toBeFalse();
});

it('updates a user role', function (): void {
    $admin = makeAdminUser();
    $user = makeRegularUser();
    $repository = new AdminUserStubRepository(findResult: $user);
    $auth = makeAdminUserControllerAuthManager(user: $admin);
    $controller = makeAdminUserController(repository: $repository, auth: $auth);

    $request = new Request(
        server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin/users/2/role'],
        post: ['role' => 'admin'],
    );
    $response = $controller->updateRole(id: '2', request: $request);

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(302)
        ->and($response->headers()['Location'])->toBe('/admin/users')
        ->and($repository->savedUser)->not->toBeNull()
        ->and($repository->savedUser->role)->toBe(UserRole::Admin);
});

it('prevents admin from banning themselves', function (): void {
    $admin = makeAdminUser();
    $repository = new AdminUserStubRepository(findResult: $admin);
    $auth = makeAdminUserControllerAuthManager(user: $admin);
    $controller = makeAdminUserController(repository: $repository, auth: $auth);

    $response = $controller->ban(id: '1');

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(422)
        ->and($repository->savedUser)->toBeNull();
});

it('requires admin role for all actions', function (): void {
    $reflection = new ReflectionClass(objectOrClass: AdminUserController::class);
    $methods = ['index', 'ban', 'unban', 'updateRole'];

    foreach ($methods as $methodName) {
        $method = $reflection->getMethod(name: $methodName);
        $routeAttributes = $method->getAttributes();
        $hasAdminMiddleware = false;

        foreach ($routeAttributes as $attribute) {
            $instance = $attribute->newInstance();
            if (property_exists(object_or_class: $instance, property: 'middleware')) {
                $middleware = $instance->middleware;
                if (in_array(needle: AdminMiddleware::class, haystack: $middleware, strict: true)) {
                    $hasAdminMiddleware = true;
                    break;
                }
            }
        }

        expect($hasAdminMiddleware)->toBeTrue("Method $methodName must have AdminMiddleware");
    }
});
