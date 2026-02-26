<?php

declare(strict_types=1);

use App\User\Controller\ProfileController;
use App\User\Entity\User;
use App\User\Enum\UserRole;
use App\User\Repository\UserRepositoryInterface;
use Marko\Authentication\AuthManager;
use Marko\Authentication\AuthenticatableInterface;
use Marko\Database\Entity\Entity;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\Testing\Fake\FakeGuard;
use Marko\Validation\Contracts\ValidatorInterface;
use Marko\Validation\Validation\ValidationErrors;
use Marko\View\ViewInterface;

// ─── Stub classes ────────────────────────────────────────────────────────────

class ProfileStubView implements ViewInterface
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

        return Response::html(html: '<html>profile</html>');
    }

    public function renderToString(
        string $template,
        array $data = [],
    ): string {
        $this->lastTemplate = $template;
        $this->lastData = $data;

        return '<html>profile</html>';
    }
}

class ProfileStubUserRepository implements UserRepositoryInterface
{
    public ?User $savedUser = null;

    public function __construct(
        private ?User $user = null,
    ) {}

    public function findByEmail(string $email): ?User { return null; }

    public function findByUsername(string $username): ?User { return null; }

    public function findByRememberToken(int $userId, string $token): ?User { return null; }

    public function updateRememberToken(User $user, ?string $token): void {}

    public function updateLastSeen(User $user, DateTimeImmutable $timestamp): void {}

    public function clearLastSeen(User $user): void {}

    public function find(int $id): ?Entity { return $this->user; }

    public function findOrFail(int $id): Entity
    {
        if ($this->user === null) {
            throw new RuntimeException(message: 'User not found');
        }

        return $this->user;
    }

    public function findAll(): array { return []; }

    public function findBy(array $criteria): array { return []; }

    public function findOneBy(array $criteria): ?Entity { return null; }

    public function save(Entity $entity): void
    {
        $this->savedUser = $entity instanceof User ? $entity : null;
    }

    public function delete(Entity $entity): void {}
}

class ProfileStubValidator implements ValidatorInterface
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
            throw Marko\Validation\Exceptions\ValidationException::withErrors(errors: $this->errors);
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

// ─── Helper factories ─────────────────────────────────────────────────────────

function makeProfileUser(int $id = 1, string $displayName = 'John Doe', ?string $avatarUrl = null): User
{
    return new User(
        id: $id,
        username: 'johndoe',
        email: 'john@example.com',
        password: 'hashed_password',
        displayName: $displayName,
        avatarUrl: $avatarUrl,
        role: UserRole::User,
        isBanned: false,
        lastSeenAt: null,
        rememberToken: null,
        createdAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
        updatedAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );
}

function makeProfileAuthManager(?AuthenticatableInterface $user = null): AuthManager
{
    return new class (mockUser: $user) extends AuthManager {
        public function __construct(
            private readonly ?AuthenticatableInterface $mockUser,
        ) {
            // Skip parent constructor
        }

        public function user(): ?AuthenticatableInterface
        {
            return $this->mockUser;
        }
    };
}

function makeProfileController(
    ?ProfileStubUserRepository $repository = null,
    ?ProfileStubView $view = null,
    ?AuthManager $auth = null,
    ?ProfileStubValidator $validator = null,
): ProfileController {
    return new ProfileController(
        userRepository: $repository ?? new ProfileStubUserRepository(),
        view: $view ?? new ProfileStubView(),
        auth: $auth ?? makeProfileAuthManager(),
        validator: $validator ?? new ProfileStubValidator(),
    );
}

function makeProfileGetRequest(): Request
{
    return new Request(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/profile']);
}

function makeProfilePostRequest(array $post = []): Request
{
    return new Request(
        server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/profile'],
        post: $post,
    );
}

// ─── Tests ────────────────────────────────────────────────────────────────────

it('shows the profile edit form with current user data', function (): void {
    $user = makeProfileUser(avatarUrl: 'https://example.com/avatar.png');
    $view = new ProfileStubView();
    $auth = makeProfileAuthManager(user: $user);
    $repository = new ProfileStubUserRepository(user: $user);
    $controller = makeProfileController(repository: $repository, view: $view, auth: $auth);

    $response = $controller->edit(request: makeProfileGetRequest());

    expect($response->statusCode())->toBe(200)
        ->and($view->lastTemplate)->toBe('user::profile/edit')
        ->and($view->lastData['user'])->toBe($user);
});

it('updates display_name on valid submission', function (): void {
    $user = makeProfileUser(displayName: 'Old Name');
    $repository = new ProfileStubUserRepository(user: $user);
    $auth = makeProfileAuthManager(user: $user);
    $controller = makeProfileController(repository: $repository, auth: $auth);

    $response = $controller->update(request: makeProfilePostRequest(post: [
        'display_name' => 'New Name',
    ]));

    expect($response->statusCode())->toBe(302)
        ->and($response->headers()['Location'])->toBe('/profile')
        ->and($repository->savedUser)->not->toBeNull()
        ->and($repository->savedUser->displayName)->toBe('New Name');
});

it('updates avatar_url on valid submission', function (): void {
    $user = makeProfileUser();
    $repository = new ProfileStubUserRepository(user: $user);
    $auth = makeProfileAuthManager(user: $user);
    $controller = makeProfileController(repository: $repository, auth: $auth);

    $response = $controller->update(request: makeProfilePostRequest(post: [
        'display_name' => 'John Doe',
        'avatar_url' => 'https://example.com/new-avatar.png',
    ]));

    expect($response->statusCode())->toBe(302)
        ->and($repository->savedUser)->not->toBeNull()
        ->and($repository->savedUser->avatarUrl)->toBe('https://example.com/new-avatar.png');
});

it('validates display_name is required and max 100 characters', function (): void {
    $user = makeProfileUser();
    $repository = new ProfileStubUserRepository(user: $user);
    $auth = makeProfileAuthManager(user: $user);
    $errors = new ValidationErrors();
    $errors->add(field: 'display_name', message: 'The display_name field is required.');
    $validator = new ProfileStubValidator(errors: $errors);
    $view = new ProfileStubView();
    $controller = makeProfileController(repository: $repository, view: $view, auth: $auth, validator: $validator);

    $response = $controller->update(request: makeProfilePostRequest(post: [
        'display_name' => '',
    ]));

    expect($response->statusCode())->toBe(200)
        ->and($view->lastTemplate)->toBe('user::profile/edit')
        ->and($view->lastData['errors'])->toBeInstanceOf(ValidationErrors::class)
        ->and($view->lastData['errors']->has(field: 'display_name'))->toBeTrue()
        ->and($repository->savedUser)->toBeNull();
});

it('requires authentication', function (): void {
    $auth = makeProfileAuthManager();
    $controller = makeProfileController(auth: $auth);

    $editResponse = $controller->edit(request: makeProfileGetRequest());
    $updateResponse = $controller->update(request: makeProfilePostRequest(post: ['display_name' => 'Test']));

    expect($editResponse->statusCode())->toBe(302)
        ->and($editResponse->headers()['Location'])->toBe('/login')
        ->and($updateResponse->statusCode())->toBe(302)
        ->and($updateResponse->headers()['Location'])->toBe('/login');
});
