<?php

declare(strict_types=1);

use App\Message\Controller\MessageController;
use App\Message\Entity\Message;
use App\Message\Repository\MessageRepositoryInterface;
use App\Space\Entity\Space;
use App\Space\Repository\SpaceRepositoryInterface;
use App\User\Entity\User;
use App\User\Enum\UserRole;
use Marko\Authentication\AuthManager;
use Marko\Authentication\AuthenticatableInterface;
use Marko\Config\ConfigRepositoryInterface;
use Marko\Config\Exceptions\ConfigNotFoundException;
use Marko\Database\Entity\Entity as DatabaseEntity;
use Marko\Pagination\CursorPaginator;
use Marko\RateLimiting\Contracts\RateLimiterInterface;
use Marko\RateLimiting\RateLimitResult;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\Validation\Contracts\ValidatorInterface;
use Marko\Validation\Exceptions\ValidationException;
use Marko\Validation\Validation\ValidationErrors;

// Helper factories

function makeRateLimitUser(int $id = 1, UserRole $role = UserRole::User): User
{
    return new User(
        id: $id,
        username: 'testuser',
        email: 'test@example.com',
        password: 'hashed_password',
        displayName: 'Test User',
        avatarUrl: null,
        role: $role,
        isBanned: false,
        lastSeenAt: null,
        rememberToken: null,
        createdAt: new DateTimeImmutable('2026-02-24 00:00:00'),
        updatedAt: new DateTimeImmutable('2026-02-24 00:00:00'),
    );
}

function makeRateLimitSpace(int $id = 1, string $slug = 'general'): Space
{
    return new Space(
        id: $id,
        name: 'General',
        slug: $slug,
        description: null,
        isArchived: false,
        createdBy: 1,
        createdAt: new DateTimeImmutable('2026-02-24 00:00:00'),
        updatedAt: new DateTimeImmutable('2026-02-24 00:00:00'),
    );
}

function makeRateLimitAuthManager(?AuthenticatableInterface $user = null): AuthManager
{
    /** @noinspection PhpMissingParentConstructorInspection - Test stub intentionally skips parent */
    return new class ($user) extends AuthManager {
        /** @noinspection PhpMissingParentConstructorInspection */
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

function makeRateLimitSpaceRepository(?Space $space = null): SpaceRepositoryInterface
{
    return new class ($space) implements SpaceRepositoryInterface {
        public function __construct(
            private readonly ?Space $space,
        ) {}

        public function findBySlug(string $slug): ?Space
        {
            return $this->space;
        }

        public function findActive(): array
        {
            return $this->space !== null ? [$this->space] : [];
        }

        public function find(int $id): ?DatabaseEntity
        {
            return $this->space;
        }

        public function findOrFail(int $id): DatabaseEntity
        {
            return $this->space ?? throw new RuntimeException(message: 'Not found');
        }

        public function findAll(): array
        {
            return $this->space !== null ? [$this->space] : [];
        }

        public function save(DatabaseEntity $entity): void {}

        public function delete(DatabaseEntity $entity): void {}

        public function findOneBy(array $criteria): ?DatabaseEntity
        {
            return $this->space;
        }

        public function existsBy(array $criteria): bool { return $this->findOneBy(criteria: $criteria) !== null; }

        public function findBy(array $criteria): array
        {
            return $this->space !== null ? [$this->space] : [];
        }
    };
}

function makeRateLimitMessageRepository(): MessageRepositoryInterface
{
    return new class () implements MessageRepositoryInterface {
        /** @var array<Message> */
        public array $savedMessages = [];

        public function findPinnedBySpace(int $spaceId): array
        {
            return [];
        }

        public function findBySpace(int $spaceId, int $limit = 50): array
        {
            return [];
        }

        public function findBySpaceSince(int $spaceId, int $sinceId): array
        {
            return [];
        }

        public function findEditedSince(int $spaceId, DateTimeImmutable $since): array
        {
            return [];
        }

        public function find(int $id): ?DatabaseEntity
        {
            return null;
        }

        public function findOrFail(int $id): DatabaseEntity
        {
            throw new RuntimeException(message: "Message $id not found");
        }

        public function findAll(): array
        {
            return [];
        }

        public function save(DatabaseEntity $entity): void
        {
            $this->savedMessages[] = $entity;
        }

        public function delete(DatabaseEntity $entity): void {}

        public function findOneBy(array $criteria): ?DatabaseEntity
        {
            return null;
        }

        public function existsBy(array $criteria): bool { return $this->findOneBy(criteria: $criteria) !== null; }

        public function findBy(array $criteria): array
        {
            return [];
        }

        public function findPaginated(int $spaceId, int $perPage = 50, ?string $cursor = null): CursorPaginator
        {
            return new CursorPaginator(items: [], perPage: $perPage, nextCursor: null);
        }
    };
}

function makeRateLimitValidator(bool $passes = true): ValidatorInterface
{
    return new class ($passes) implements ValidatorInterface {
        public function __construct(
            private readonly bool $passes,
        ) {}

        public function validate(array $data, array $rules): ValidationErrors
        {
            return new ValidationErrors();
        }

        public function validateOrFail(array $data, array $rules): void
        {
            if (!$this->passes) {
                $errors = new ValidationErrors();
                $errors->add(field: 'body', message: 'The body field is required.');
                throw ValidationException::withErrors(errors: $errors);
            }
        }

        public function passes(array $data, array $rules): bool
        {
            return $this->passes;
        }

        public function fails(array $data, array $rules): bool
        {
            return !$this->passes;
        }
    };
}

function makeRateLimitConfig(
    int $maxMessageLength = 4000,
    int $rateLimitMessages = 10,
    int $rateLimitWindow = 30,
): ConfigRepositoryInterface {
    return new class ($maxMessageLength, $rateLimitMessages, $rateLimitWindow) implements ConfigRepositoryInterface {
        public function __construct(
            private readonly int $maxMessageLength,
            private readonly int $rateLimitMessages,
            private readonly int $rateLimitWindow,
        ) {}

        public function get(string $key, ?string $scope = null): mixed
        {
            return match ($key) {
                'markotalk.max_message_length' => $this->maxMessageLength,
                'markotalk.rate_limit_messages' => $this->rateLimitMessages,
                'markotalk.rate_limit_window' => $this->rateLimitWindow,
                default => null,
            };
        }

        public function has(string $key, ?string $scope = null): bool
        {
            return in_array(needle: $key, haystack: [
                'markotalk.max_message_length',
                'markotalk.rate_limit_messages',
                'markotalk.rate_limit_window',
            ], strict: true);
        }

        public function getString(string $key, ?string $scope = null): string
        {
            return (string) $this->get(key: $key, scope: $scope);
        }

        public function getInt(string $key, ?string $scope = null): int
        {
            $value = $this->get(key: $key, scope: $scope);

            if ($value === null) {
                throw new ConfigNotFoundException(key: $key);
            }

            return (int) $value;
        }

        public function getBool(string $key, ?string $scope = null): bool
        {
            return (bool) $this->get(key: $key, scope: $scope);
        }

        public function getFloat(string $key, ?string $scope = null): float
        {
            return (float) $this->get(key: $key, scope: $scope);
        }

        public function getArray(string $key, ?string $scope = null): array
        {
            return [];
        }

        public function all(?string $scope = null): array
        {
            return [
                'max_message_length' => $this->maxMessageLength,
                'rate_limit_messages' => $this->rateLimitMessages,
                'rate_limit_window' => $this->rateLimitWindow,
            ];
        }

        public function withScope(string $scope): ConfigRepositoryInterface
        {
            return $this;
        }
    };
}

function makeRateLimiter(bool $allowed = true, int $retryAfter = 30): RateLimiterInterface
{
    return new class ($allowed, $retryAfter) implements RateLimiterInterface {
        public function __construct(
            private readonly bool $allowed,
            private readonly int $retryAfter,
        ) {}

        public function attempt(
            string $key,
            int $maxAttempts,
            int $decaySeconds,
        ): RateLimitResult {
            return new RateLimitResult(
                allowed: $this->allowed,
                remaining: $this->allowed ? $maxAttempts - 1 : 0,
                retryAfter: $this->allowed ? null : $this->retryAfter,
            );
        }

        public function tooManyAttempts(
            string $key,
            int $maxAttempts,
        ): bool {
            return !$this->allowed;
        }

        public function clear(
            string $key,
        ): void {}
    };
}

function makeRateLimitController(
    MessageRepositoryInterface $messages,
    SpaceRepositoryInterface $spaces,
    AuthManager $auth,
    ValidatorInterface $validator,
    ConfigRepositoryInterface $config,
    RateLimiterInterface $rateLimiter,
): MessageController {
    return new MessageController(
        messageRepository: $messages,
        spaceRepository: $spaces,
        auth: $auth,
        validator: $validator,
        configRepository: $config,
        rateLimiter: $rateLimiter,
    );
}

function makeRateLimitSendRequest(string $slug = 'general', string $body = 'Hello world'): Request
{
    return new Request(
        server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => "/spaces/$slug/messages"],
        post: ['body' => $body],
    );
}

// Shared test setup

function makeRateLimitSendController(bool $rateLimiterAllows = true, int $retryAfter = 30): MessageController
{
    return makeRateLimitController(
        messages: makeRateLimitMessageRepository(),
        spaces: makeRateLimitSpaceRepository(space: makeRateLimitSpace()),
        auth: makeRateLimitAuthManager(user: makeRateLimitUser()),
        validator: makeRateLimitValidator(passes: true),
        config: makeRateLimitConfig(),
        rateLimiter: makeRateLimiter(allowed: $rateLimiterAllows, retryAfter: $retryAfter),
    );
}

// Tests

it('allows messages within the rate limit', function (): void {
    $user = makeRateLimitUser();
    $space = makeRateLimitSpace();
    $messages = makeRateLimitMessageRepository();
    $spaces = makeRateLimitSpaceRepository(space: $space);
    $auth = makeRateLimitAuthManager(user: $user);
    $validator = makeRateLimitValidator(passes: true);
    $config = makeRateLimitConfig();
    $rateLimiter = makeRateLimiter(allowed: true);

    $controller = makeRateLimitController(
        messages: $messages,
        spaces: $spaces,
        auth: $auth,
        validator: $validator,
        config: $config,
        rateLimiter: $rateLimiter,
    );

    $response = $controller->send(slug: 'general', request: makeRateLimitSendRequest());

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(201);
});

it('returns 429 when rate limit is exceeded', function (): void {
    $controller = makeRateLimitSendController(rateLimiterAllows: false);

    $response = $controller->send(slug: 'general', request: makeRateLimitSendRequest());

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(429);
});

it('includes retry-after header in rate limit response', function (): void {
    $controller = makeRateLimitSendController(rateLimiterAllows: false, retryAfter: 30);

    $response = $controller->send(slug: 'general', request: makeRateLimitSendRequest());

    expect($response->headers())->toHaveKey('Retry-After')
        ->and($response->headers()['Retry-After'])->toBe('30');
});

it('limits per user, not globally', function (): void {
    $attemptedKeys = [];

    $rateLimiter = new class ($attemptedKeys) implements RateLimiterInterface {
        public function __construct(
            /** @noinspection PhpPropertyOnlyWrittenInspection - Reference property modifies external variable */
            private array &$attemptedKeys,
        ) {}

        public function attempt(string $key, int $maxAttempts, int $decaySeconds): RateLimitResult
        {
            $this->attemptedKeys[] = $key;

            return new RateLimitResult(allowed: true, remaining: $maxAttempts - 1);
        }

        public function tooManyAttempts(string $key, int $maxAttempts): bool
        {
            return false;
        }

        public function clear(string $key): void {}
    };

    $space = makeRateLimitSpace();
    $config = makeRateLimitConfig();

    $userOne = makeRateLimitUser(id: 1);
    $controllerOne = makeRateLimitController(
        messages: makeRateLimitMessageRepository(),
        spaces: makeRateLimitSpaceRepository(space: $space),
        auth: makeRateLimitAuthManager(user: $userOne),
        validator: makeRateLimitValidator(passes: true),
        config: $config,
        rateLimiter: $rateLimiter,
    );
    $controllerOne->send(slug: 'general', request: makeRateLimitSendRequest());

    $userTwo = makeRateLimitUser(id: 2);
    $controllerTwo = makeRateLimitController(
        messages: makeRateLimitMessageRepository(),
        spaces: makeRateLimitSpaceRepository(space: $space),
        auth: makeRateLimitAuthManager(user: $userTwo),
        validator: makeRateLimitValidator(passes: true),
        config: $config,
        rateLimiter: $rateLimiter,
    );
    $controllerTwo->send(slug: 'general', request: makeRateLimitSendRequest());

    expect($attemptedKeys)->toHaveCount(2)
        ->and($attemptedKeys[0])->toBe('user_1_messages')
        ->and($attemptedKeys[1])->toBe('user_2_messages');
});

it('reads rate limit config from markotalk.php', function (): void {
    $capturedMaxAttempts = null;
    $capturedDecaySeconds = null;

    $rateLimiter = new class ($capturedMaxAttempts, $capturedDecaySeconds) implements RateLimiterInterface {
        public function __construct(
            /** @noinspection PhpPropertyOnlyWrittenInspection - Reference property modifies external variable */
            private mixed &$capturedMaxAttempts,
            /** @noinspection PhpPropertyOnlyWrittenInspection - Reference property modifies external variable */
            private mixed &$capturedDecaySeconds,
        ) {}

        public function attempt(string $key, int $maxAttempts, int $decaySeconds): RateLimitResult
        {
            $this->capturedMaxAttempts = $maxAttempts;
            $this->capturedDecaySeconds = $decaySeconds;

            return new RateLimitResult(allowed: true, remaining: $maxAttempts - 1);
        }

        public function tooManyAttempts(string $key, int $maxAttempts): bool
        {
            return false;
        }

        public function clear(string $key): void {}
    };

    $controller = makeRateLimitController(
        messages: makeRateLimitMessageRepository(),
        spaces: makeRateLimitSpaceRepository(space: makeRateLimitSpace()),
        auth: makeRateLimitAuthManager(user: makeRateLimitUser()),
        validator: makeRateLimitValidator(passes: true),
        config: makeRateLimitConfig(rateLimitMessages: 5, rateLimitWindow: 60),
        rateLimiter: $rateLimiter,
    );

    $controller->send(slug: 'general', request: makeRateLimitSendRequest());

    expect($capturedMaxAttempts)->toBe(5)
        ->and($capturedDecaySeconds)->toBe(60);
});
