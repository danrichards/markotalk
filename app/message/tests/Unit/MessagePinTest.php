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
use Marko\Authorization\Contracts\GateInterface;
use Marko\Authorization\Exceptions\AuthorizationException;
use Marko\Config\ConfigRepositoryInterface;
use Marko\Config\Exceptions\ConfigNotFoundException;
use Marko\Database\Entity\Entity as DatabaseEntity;
use Marko\Pagination\CursorPaginator;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\Validation\Contracts\ValidatorInterface;
use Marko\Validation\Validation\ValidationErrors;

// Helper factories

function makePinUser(int $id = 1, UserRole $role = UserRole::Admin): User
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

function makePinMessage(int $id = 1, bool $isPinned = false): Message
{
    return new Message(
        id: $id,
        spaceId: 1,
        userId: 2,
        body: 'Hello world',
        bodyHtml: '<p>Hello world</p>',
        isPinned: $isPinned,
        editedAt: null,
        createdAt: new DateTimeImmutable('2026-02-24 00:00:00'),
    );
}

function makePinMessageRepository(array $messages = []): MessageRepositoryInterface
{
    return new class ($messages) implements MessageRepositoryInterface {
        /** @var array<Message> */
        public array $savedMessages = [];

        public function __construct(
            private readonly array $messages,
        ) {}

        public function findPinnedBySpace(int $spaceId): array
        {
            return array_values(array: array_filter(
                array: $this->messages,
                callback: fn (Message $m) => $m->spaceId === $spaceId && $m->isPinned,
            ));
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
            return array_find(array: $this->messages, callback: fn (Message $message) => $message->id === $id);
        }

        public function findOrFail(int $id): DatabaseEntity
        {
            $entity = $this->find(id: $id);

            if ($entity === null) {
                throw new RuntimeException(message: "Message $id not found");
            }

            return $entity;
        }

        public function findAll(): array
        {
            return $this->messages;
        }

        public function save(DatabaseEntity $entity): void
        {
            $this->savedMessages[] = $entity;
        }

        public function delete(DatabaseEntity $entity): void {}

        public function findPaginated(int $spaceId, int $perPage = 50, ?string $cursor = null): CursorPaginator
        {
            return new CursorPaginator(items: [], perPage: $perPage, nextCursor: null, prevCursor: null);
        }

        public function findOneBy(array $criteria): ?DatabaseEntity
        {
            return null;
        }

        public function existsBy(array $criteria): bool { return $this->findOneBy(criteria: $criteria) !== null; }

        public function findBy(array $criteria): array
        {
            return $this->messages;
        }
    };
}

function makePinAuthManager(?AuthenticatableInterface $user = null): AuthManager
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

function makeAllowingGate(): GateInterface
{
    return new class implements GateInterface {
        public function define(string $ability, callable $callback): void {}

        public function allows(string $ability, mixed ...$arguments): bool
        {
            return true;
        }

        public function denies(string $ability, mixed ...$arguments): bool
        {
            return false;
        }

        public function authorize(string $ability, mixed ...$arguments): bool
        {
            return true;
        }

        public function policy(string $entityClass, string $policyClass): void {}
    };
}

function makeDenyingGate(): GateInterface
{
    return new class implements GateInterface {
        public function define(string $ability, callable $callback): void {}

        public function allows(string $ability, mixed ...$arguments): bool
        {
            return false;
        }

        public function denies(string $ability, mixed ...$arguments): bool
        {
            return true;
        }

        public function authorize(string $ability, mixed ...$arguments): bool
        {
            throw AuthorizationException::forbidden(ability: $ability, resource: 'message');
        }

        public function policy(string $entityClass, string $policyClass): void {}
    };
}

function makePinSpaceRepository(): SpaceRepositoryInterface
{
    return new class implements SpaceRepositoryInterface {
        public function findBySlug(string $slug): ?Space
        {
            return null;
        }

        public function findActive(): array
        {
            return [];
        }

        public function find(int $id): ?DatabaseEntity
        {
            return null;
        }

        public function findOrFail(int $id): DatabaseEntity
        {
            throw new RuntimeException(message: 'Not found');
        }

        public function findAll(): array
        {
            return [];
        }

        public function save(DatabaseEntity $entity): void {}

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
    };
}

function makePinValidator(): ValidatorInterface
{
    return new class implements ValidatorInterface {
        public function validate(array $data, array $rules): ValidationErrors
        {
            return new ValidationErrors();
        }

        public function validateOrFail(array $data, array $rules): void {}

        public function passes(array $data, array $rules): bool
        {
            return true;
        }

        public function fails(array $data, array $rules): bool
        {
            return false;
        }
    };
}

function makePinConfig(): ConfigRepositoryInterface
{
    return new class implements ConfigRepositoryInterface {
        public function get(string $key, ?string $scope = null): mixed
        {
            return null;
        }

        public function has(string $key, ?string $scope = null): bool
        {
            return false;
        }

        public function getString(string $key, ?string $scope = null): string
        {
            return '';
        }

        public function getInt(string $key, ?string $scope = null): int
        {
            throw new ConfigNotFoundException(key: $key);
        }

        public function getBool(string $key, ?string $scope = null): bool
        {
            return false;
        }

        public function getFloat(string $key, ?string $scope = null): float
        {
            return 0.0;
        }

        public function getArray(string $key, ?string $scope = null): array
        {
            return [];
        }

        public function all(?string $scope = null): array
        {
            return [];
        }

        public function withScope(string $scope): ConfigRepositoryInterface
        {
            return $this;
        }
    };
}

function makePinController(
    MessageRepositoryInterface $messages,
    GateInterface $gate,
    ?AuthManager $auth = null,
): MessageController {
    return new MessageController(
        messageRepository: $messages,
        spaceRepository: makePinSpaceRepository(),
        auth: $auth ?? makePinAuthManager(user: makePinUser()),
        validator: makePinValidator(),
        config: makePinConfig(),
        gate: $gate,
    );
}

function makePinRequest(int $id = 1, string $referer = ''): Request
{
    $server = ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => "/messages/$id/pin"];

    if ($referer !== '') {
        $server['HTTP_REFERER'] = $referer;
    }

    return new Request(server: $server);
}

// Tests

it('pins a message when admin toggles pin', function (): void {
    $message = makePinMessage(id: 1, isPinned: false);
    $messages = makePinMessageRepository(messages: [$message]);
    $gate = makeAllowingGate();
    $controller = makePinController(messages: $messages, gate: $gate);

    $response = $controller->pin(id: 1, request: makePinRequest());

    expect($response)->toBeInstanceOf(Response::class)
        ->and($message->isPinned)->toBeTrue()
        ->and($messages->savedMessages)->toHaveCount(1)
        ->and($messages->savedMessages[0]->isPinned)->toBeTrue();
});

it('unpins a pinned message when admin toggles pin', function (): void {
    $message = makePinMessage(id: 1, isPinned: true);
    $messages = makePinMessageRepository(messages: [$message]);
    $gate = makeAllowingGate();
    $controller = makePinController(messages: $messages, gate: $gate);

    $response = $controller->pin(id: 1, request: makePinRequest());

    expect($response)->toBeInstanceOf(Response::class)
        ->and($message->isPinned)->toBeFalse()
        ->and($messages->savedMessages)->toHaveCount(1)
        ->and($messages->savedMessages[0]->isPinned)->toBeFalse();
});

it('rejects pin action from non-admin users', function (): void {
    $message = makePinMessage(id: 1);
    $messages = makePinMessageRepository(messages: [$message]);
    $gate = makeDenyingGate();
    $controller = makePinController(messages: $messages, gate: $gate);

    $response = $controller->pin(id: 1, request: makePinRequest());

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(403)
        ->and($messages->savedMessages)->toBeEmpty();
});

it('returns 403 when unauthorized', function (): void {
    $message = makePinMessage(id: 1);
    $messages = makePinMessageRepository(messages: [$message]);
    $gate = makeDenyingGate();
    $controller = makePinController(messages: $messages, gate: $gate);

    $response = $controller->pin(id: 1, request: makePinRequest());

    expect($response->statusCode())->toBe(403);
});

it('redirects back to the space after pin/unpin', function (): void {
    $message = makePinMessage(id: 1, isPinned: false);
    $messages = makePinMessageRepository(messages: [$message]);
    $gate = makeAllowingGate();
    $controller = makePinController(messages: $messages, gate: $gate);

    $response = $controller->pin(id: 1, request: makePinRequest(referer: '/spaces/general'));

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(302)
        ->and($response->headers())->toHaveKey('Location')
        ->and($response->headers()['Location'])->toBe('/spaces/general');
});
