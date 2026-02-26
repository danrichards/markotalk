<?php

declare(strict_types=1);

use App\Space\Entity\Space;
use App\Space\Repository\SpaceRepositoryInterface;
use App\User\Controller\AuthController;
use App\User\Entity\User;
use App\User\Enum\UserRole;
use App\User\Event\UserRegisteredEvent;
use App\User\Repository\UserRepositoryInterface;
use App\User\Service\PresenceTrackerInterface;
use Marko\Authentication\Contracts\GuardInterface;
use Marko\Core\Event\EventDispatcherInterface;
use Marko\Database\Entity\Entity;
use Marko\Hashing\Contracts\HasherInterface;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\Testing\Fake\FakeAuthenticatable;
use Marko\Testing\Fake\FakeEventDispatcher;
use Marko\Testing\Fake\FakeGuard;
use Marko\Validation\Contracts\ValidatorInterface;
use Marko\Validation\Exceptions\ValidationException;
use Marko\Validation\Validation\ValidationErrors;
use Marko\Security\Contracts\CsrfTokenManagerInterface;
use Marko\View\ViewInterface;

// Stub CsrfTokenManagerInterface for tests
class AuthStubCsrfTokenManager implements CsrfTokenManagerInterface
{
    public function get(): string { return 'test-token'; }

    public function validate(string $token): bool { return true; }

    public function regenerate(): string { return 'test-token'; }
}

// Stub ViewInterface for tests
class AuthStubView implements ViewInterface
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

        return Response::html(html: '<html>stub</html>');
    }

    public function renderToString(
        string $template,
        array $data = [],
    ): string {
        $this->lastTemplate = $template;
        $this->lastData = $data;

        return '<html>stub</html>';
    }
}

// Stub UserRepositoryInterface for tests
class AuthStubUserRepository implements UserRepositoryInterface
{
    public ?User $foundByEmail = null;

    public ?User $savedUser = null;

    public function findByEmail(string $email): ?User
    {
        return $this->foundByEmail;
    }

    public function findByUsername(string $username): ?User
    {
        return null;
    }

    public function findByRememberToken(int $userId, string $token): ?User
    {
        return null;
    }

    public function updateRememberToken(User $user, ?string $token): void {}

    public function updateLastSeen(User $user, DateTimeImmutable $timestamp): void {}

    public function clearLastSeen(User $user): void {}

    public function find(int $id): ?Entity
    {
        return null;
    }

    public function findOrFail(int $id): Entity
    {
        throw new RuntimeException(message: 'Not implemented');
    }

    public function findAll(): array
    {
        return [];
    }

    public function findBy(array $criteria): array
    {
        return [];
    }

    public function findOneBy(array $criteria): ?Entity
    {
        return null;
    }

    public function save(Entity $entity): void
    {
        $this->savedUser = $entity instanceof User ? $entity : null;
    }

    public function delete(Entity $entity): void {}
}

// Stub HasherInterface for tests
class AuthStubHasher implements HasherInterface
{
    public function hash(string $value): string
    {
        return 'hashed_' . $value;
    }

    public function verify(string $value, string $hash): bool
    {
        return $hash === 'hashed_' . $value;
    }

    public function needsRehash(string $hash): bool
    {
        return false;
    }

    public function algorithm(): string
    {
        return 'stub';
    }
}

// Stub ValidatorInterface for tests
class AuthStubValidator implements ValidatorInterface
{
    public function __construct(
        private ValidationErrors $errors = new ValidationErrors(),
    ) {}

    public function validate(array $data, array $rules): ValidationErrors
    {
        return $this->errors;
    }

    public function validateOrFail(array $data, array $rules): void
    {
        if ($this->errors->isNotEmpty()) {
            throw ValidationException::withErrors(errors: $this->errors);
        }
    }

    public function passes(array $data, array $rules): bool
    {
        return $this->errors->isEmpty();
    }

    public function fails(array $data, array $rules): bool
    {
        return $this->errors->isNotEmpty();
    }
}

function makeAuthStubPresenceTracker(): PresenceTrackerInterface
{
    return new class implements PresenceTrackerInterface {
        public function updateLastSeen(User $user): void {}
        public function markOffline(User $user): void {}
        public function isOnline(User $user): bool { return false; }
        public function getOnlineUsers(): array { return []; }
    };
}

function makeAuthStubSpaceRepository(): SpaceRepositoryInterface
{
    return new class implements SpaceRepositoryInterface {
        public function findBySlug(string $slug): ?Space { return null; }
        public function findActive(): array { return []; }
        public function find(int $id): ?Entity { return null; }
        public function findOrFail(int $id): Entity { throw new RuntimeException(message: 'Not implemented'); }
        public function findAll(): array { return []; }
        public function findBy(array $criteria): array { return []; }
        public function findOneBy(array $criteria): ?Entity { return null; }
        public function save(Entity $entity): void {}
        public function delete(Entity $entity): void {}
    };
}

function makeAuthController(
    ?AuthStubView $view = null,
    ?FakeGuard $guard = null,
    ?AuthStubUserRepository $userRepository = null,
    ?AuthStubHasher $hasher = null,
    ?AuthStubValidator $validator = null,
    ?FakeEventDispatcher $eventDispatcher = null,
    ?CsrfTokenManagerInterface $csrf = null,
): AuthController {
    return new AuthController(
        view: $view ?? new AuthStubView(),
        guard: $guard ?? new FakeGuard(),
        userRepository: $userRepository ?? new AuthStubUserRepository(),
        hasher: $hasher ?? new AuthStubHasher(),
        validator: $validator ?? new AuthStubValidator(),
        eventDispatcher: $eventDispatcher ?? new FakeEventDispatcher(),
        csrf: $csrf ?? new AuthStubCsrfTokenManager(),
        presence: makeAuthStubPresenceTracker(),
        spaces: makeAuthStubSpaceRepository(),
    );
}

it('shows the login form on GET /login', function (): void {
    $view = new AuthStubView();
    $controller = makeAuthController(view: $view);

    $request = new Request();
    $response = $controller->showLogin(request: $request);

    expect($response->statusCode())->toBe(200)
        ->and($view->lastTemplate)->toBe('user::auth/login');
});

it('shows the registration form on GET /register', function (): void {
    $view = new AuthStubView();
    $controller = makeAuthController(view: $view);

    $request = new Request();
    $response = $controller->showRegister(request: $request);

    expect($response->statusCode())->toBe(200)
        ->and($view->lastTemplate)->toBe('user::auth/register');
});

it('creates a new user on POST /register with valid data', function (): void {
    $view = new AuthStubView();
    $userRepository = new AuthStubUserRepository();
    $guard = new FakeGuard();
    $controller = makeAuthController(
        view: $view,
        guard: $guard,
        userRepository: $userRepository,
    );

    $request = new Request(post: [
        'username' => 'johndoe',
        'email' => 'john@example.com',
        'password' => 'secret123',
        'display_name' => 'John Doe',
    ]);
    $response = $controller->register(request: $request);

    expect($response->statusCode())->toBe(302)
        ->and($response->headers())->toHaveKey('Location')
        ->and($response->headers()['Location'])->toBe('/home')
        ->and($userRepository->savedUser)->not->toBeNull()
        ->and($userRepository->savedUser->email)->toBe('john@example.com')
        ->and($userRepository->savedUser->password)->toBe('hashed_secret123');
});

it('rejects registration with duplicate email', function (): void {
    $view = new AuthStubView();
    $existingUser = new User(
        id: 1,
        username: 'existing',
        email: 'john@example.com',
        password: 'hashed_password',
        displayName: 'Existing User',
        avatarUrl: null,
        role: UserRole::User,
        isBanned: false,
        lastSeenAt: null,
        rememberToken: null,
        createdAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
        updatedAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );
    $userRepository = new AuthStubUserRepository();
    $userRepository->foundByEmail = $existingUser;
    $controller = makeAuthController(view: $view, userRepository: $userRepository);

    $request = new Request(post: [
        'username' => 'johndoe',
        'email' => 'john@example.com',
        'password' => 'secret123',
        'display_name' => 'John Doe',
    ]);
    $response = $controller->register(request: $request);

    expect($response->statusCode())->toBe(200)
        ->and($view->lastTemplate)->toBe('user::auth/register')
        ->and($view->lastData)->toHaveKey('errors')
        ->and($userRepository->savedUser)->toBeNull();
});

it('rejects registration with password shorter than 8 characters', function (): void {
    $view = new AuthStubView();
    $shortPasswordErrors = new ValidationErrors();
    $shortPasswordErrors->add(field: 'password', message: 'The password field must be at least 8 characters.');
    $validator = new AuthStubValidator(errors: $shortPasswordErrors);
    $userRepository = new AuthStubUserRepository();
    $controller = makeAuthController(view: $view, userRepository: $userRepository, validator: $validator);

    $request = new Request(post: [
        'username' => 'johndoe',
        'email' => 'john@example.com',
        'password' => 'short',
        'display_name' => 'John Doe',
    ]);
    $response = $controller->register(request: $request);

    expect($response->statusCode())->toBe(200)
        ->and($view->lastTemplate)->toBe('user::auth/register')
        ->and($view->lastData)->toHaveKey('errors')
        ->and($userRepository->savedUser)->toBeNull();
});

it('logs the user out on POST /logout and redirects to login', function (): void {
    $guard = new FakeGuard();
    $user = new FakeAuthenticatable(id: 1);
    $guard->setUser(user: $user);
    $controller = makeAuthController(guard: $guard);

    $request = new Request();
    $response = $controller->logout(request: $request);

    expect($guard->logoutCalled)->toBeTrue()
        ->and($response->statusCode())->toBe(302)
        ->and($response->headers())->toHaveKey('Location')
        ->and($response->headers()['Location'])->toBe('/login');
});

it('dispatches UserRegisteredEvent after successful registration', function (): void {
    $eventDispatcher = new FakeEventDispatcher();
    $controller = makeAuthController(eventDispatcher: $eventDispatcher);

    $request = new Request(post: [
        'username' => 'johndoe',
        'email' => 'john@example.com',
        'password' => 'secret123',
        'display_name' => 'John Doe',
    ]);
    $controller->register(request: $request);

    $dispatched = $eventDispatcher->dispatched(eventClass: UserRegisteredEvent::class);
    expect($dispatched)->toHaveCount(1)
        ->and($dispatched[0])->toBeInstanceOf(UserRegisteredEvent::class)
        ->and($dispatched[0]->user)->toBeInstanceOf(User::class);
});

it('rejects login with invalid credentials', function (): void {
    $view = new AuthStubView();
    $guard = new FakeGuard();
    $guard->setAttemptResult(result: false);
    $controller = makeAuthController(view: $view, guard: $guard);

    $request = new Request(post: [
        'email' => 'john@example.com',
        'password' => 'wrongpassword',
    ]);
    $response = $controller->login(request: $request);

    expect($response->statusCode())->toBe(200)
        ->and($view->lastTemplate)->toBe('user::auth/login')
        ->and($view->lastData)->toHaveKey('errors');
});

it('authenticates a user with valid credentials on POST /login', function (): void {
    $guard = new FakeGuard();
    $guard->setAttemptResult(result: true);
    $controller = makeAuthController(guard: $guard);

    $request = new Request(post: [
        'email' => 'john@example.com',
        'password' => 'secret123',
    ]);
    $response = $controller->login(request: $request);

    expect($response->statusCode())->toBe(302)
        ->and($response->headers())->toHaveKey('Location')
        ->and($response->headers()['Location'])->toBe('/home')
        ->and($guard->attempts)->not->toBeEmpty()
        ->and($guard->attempts[0])->toBe([
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);
});

it('redirects to /home after successful registration', function (): void {
    $controller = makeAuthController();

    $request = new Request(post: [
        'username' => 'johndoe',
        'email' => 'john@example.com',
        'password' => 'secret123',
        'display_name' => 'John Doe',
    ]);
    $response = $controller->register(request: $request);

    expect($response->statusCode())->toBe(302)
        ->and($response->headers()['Location'])->toBe('/home');
});

it('redirects to /home after successful login', function (): void {
    $guard = new FakeGuard();
    $guard->setAttemptResult(result: true);
    $controller = makeAuthController(guard: $guard);

    $request = new Request(post: [
        'email' => 'john@example.com',
        'password' => 'secret123',
    ]);
    $response = $controller->login(request: $request);

    expect($response->statusCode())->toBe(302)
        ->and($response->headers()['Location'])->toBe('/home');
});
