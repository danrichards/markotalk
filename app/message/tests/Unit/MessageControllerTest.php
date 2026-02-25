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
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\Validation\Contracts\ValidatorInterface;
use Marko\Validation\Exceptions\ValidationException;
use Marko\Validation\Validation\ValidationErrors;

// Helper factories

function makeMessageUser(int $id = 1, UserRole $role = UserRole::User): User
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

function makeMessageSpace(int $id = 1, string $slug = 'general'): Space
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

function makeTestMessage(int $id = 1, int $spaceId = 1, int $userId = 1, string $body = 'Hello world'): Message
{
    return new Message(
        id: $id,
        spaceId: $spaceId,
        userId: $userId,
        body: $body,
        bodyHtml: "<p>$body</p>",
        isPinned: false,
        editedAt: null,
        createdAt: new DateTimeImmutable('2026-02-24 00:00:00'),
    );
}

function makeMessageAuthManager(?AuthenticatableInterface $user = null): AuthManager
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

function makeMessageSpaceRepository(?Space $space = null): SpaceRepositoryInterface
{
    return new readonly class ($space) implements SpaceRepositoryInterface {
        public function __construct(
            private ?Space $space,
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

        public function findBy(array $criteria): array
        {
            return $this->space !== null ? [$this->space] : [];
        }
    };
}

function makeMessageRepository(array $messages = []): MessageRepositoryInterface
{
    return new class ($messages) implements MessageRepositoryInterface {
        /** @var array<Message> */
        public array $savedMessages = [];

        public bool $deleteCalled = false;

        /** @var DatabaseEntity|null */
        public ?DatabaseEntity $deletedEntity = null;

        public function __construct(
            private readonly array $messages,
        ) {}

        public function findBySpace(int $spaceId, int $limit = 50): array
        {
            return array_values(array: array_filter(
                array: $this->messages,
                callback: fn (Message $m) => $m->spaceId === $spaceId,
            ));
        }

        public function findBySpaceSince(int $spaceId, int $sinceId): array
        {
            return array_values(array: array_filter(
                array: $this->messages,
                callback: fn (Message $m) => $m->spaceId === $spaceId && $m->id > $sinceId,
            ));
        }

        public function find(int $id): ?DatabaseEntity
        {
            foreach ($this->messages as $message) {
                if ($message->id === $id) {
                    return $message;
                }
            }

            return null;
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

        public function delete(DatabaseEntity $entity): void
        {
            $this->deleteCalled = true;
            $this->deletedEntity = $entity;
        }

        public function findOneBy(array $criteria): ?DatabaseEntity
        {
            return null;
        }

        public function findBy(array $criteria): array
        {
            return $this->messages;
        }

        public function findPaginated(int $spaceId, int $perPage = 50, ?string $cursor = null): CursorPaginator
        {
            $items = array_values(array: array_filter(
                array: $this->messages,
                callback: fn (Message $m) => $m->spaceId === $spaceId,
            ));

            return new CursorPaginator(
                items: $items,
                perPage: $perPage,
            );
        }
    };
}

function makeMessageValidator(bool $passes = true): ValidatorInterface
{
    return new readonly class ($passes) implements ValidatorInterface {
        public function __construct(
            private bool $passes,
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

function makeMessageConfig(int $maxMessageLength = 4000): ConfigRepositoryInterface
{
    return new readonly class ($maxMessageLength) implements ConfigRepositoryInterface {
        public function __construct(
            private int $maxMessageLength,
        ) {}

        public function get(string $key, ?string $scope = null): mixed
        {
            return match ($key) {
                'markotalk.max_message_length' => $this->maxMessageLength,
                default => null,
            };
        }

        public function has(string $key, ?string $scope = null): bool
        {
            return $key === 'markotalk.max_message_length';
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
            return ['max_message_length' => $this->maxMessageLength];
        }

        public function withScope(string $scope): ConfigRepositoryInterface
        {
            return $this;
        }
    };
}

function makeMessageController(
    MessageRepositoryInterface $messages,
    SpaceRepositoryInterface $spaces,
    AuthManager $auth,
    ValidatorInterface $validator,
    ConfigRepositoryInterface $config,
): MessageController {
    return new MessageController(
        messages: $messages,
        spaces: $spaces,
        auth: $auth,
        validator: $validator,
        config: $config,
    );
}

function makePostMessageRequest(string $slug = 'general', string $body = 'Hello world'): Request
{
    return new Request(
        server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => "/spaces/$slug/messages"],
        post: ['body' => $body],
    );
}

function makeEditMessageRequest(int $id = 1, string $body = 'Updated body'): Request
{
    return new Request(
        server: ['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => "/messages/$id"],
        post: ['body' => $body],
    );
}

function makeDeleteMessageRequest(int $id = 1): Request
{
    return new Request(
        server: ['REQUEST_METHOD' => 'DELETE', 'REQUEST_URI' => "/messages/$id"],
    );
}

function makeHistoryRequest(string $slug = 'general'): Request
{
    return new Request(
        server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => "/spaces/$slug/messages"],
    );
}

// Tests

it('sends a new message on POST /spaces/{slug}/messages', function (): void {
    $user = makeMessageUser();
    $space = makeMessageSpace();
    $messages = makeMessageRepository();
    $spaces = makeMessageSpaceRepository(space: $space);
    $auth = makeMessageAuthManager(user: $user);
    $validator = makeMessageValidator(passes: true);
    $config = makeMessageConfig();
    $controller = makeMessageController(messages: $messages, spaces: $spaces, auth: $auth, validator: $validator, config: $config);

    $response = $controller->send(slug: 'general', request: makePostMessageRequest());

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(201)
        ->and($messages->savedMessages)->toHaveCount(1)
        ->and($messages->savedMessages[0]->body)->toBe('Hello world')
        ->and($messages->savedMessages[0]->spaceId)->toBe(1)
        ->and($messages->savedMessages[0]->userId)->toBe(1);
});

it('validates message body is required and max 4000 characters', function (): void {
    $user = makeMessageUser();
    $space = makeMessageSpace();
    $messages = makeMessageRepository();
    $spaces = makeMessageSpaceRepository(space: $space);
    $auth = makeMessageAuthManager(user: $user);
    $validator = makeMessageValidator(passes: false);
    $config = makeMessageConfig();
    $controller = makeMessageController(messages: $messages, spaces: $spaces, auth: $auth, validator: $validator, config: $config);

    $response = $controller->send(slug: 'general', request: makePostMessageRequest(body: ''));

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(422)
        ->and($messages->savedMessages)->toBeEmpty();
});

it('edits a message on PUT /messages/{id} by the message author', function (): void {
    $user = makeMessageUser(id: 1);
    $message = makeTestMessage(id: 1, spaceId: 1, userId: 1);
    $messages = makeMessageRepository(messages: [$message]);
    $spaces = makeMessageSpaceRepository();
    $auth = makeMessageAuthManager(user: $user);
    $validator = makeMessageValidator(passes: true);
    $config = makeMessageConfig();
    $controller = makeMessageController(messages: $messages, spaces: $spaces, auth: $auth, validator: $validator, config: $config);

    $response = $controller->edit(id: 1, request: makeEditMessageRequest(id: 1, body: 'Updated body'));

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(200)
        ->and($messages->savedMessages)->toHaveCount(1)
        ->and($messages->savedMessages[0]->body)->toBe('Updated body')
        ->and($messages->savedMessages[0]->editedAt)->toBeInstanceOf(DateTimeImmutable::class);
});

it('rejects editing a message by a non-author non-admin', function (): void {
    $user = makeMessageUser(id: 2, role: UserRole::User);
    $message = makeTestMessage(id: 1, spaceId: 1, userId: 1);
    $messages = makeMessageRepository(messages: [$message]);
    $spaces = makeMessageSpaceRepository();
    $auth = makeMessageAuthManager(user: $user);
    $validator = makeMessageValidator(passes: true);
    $config = makeMessageConfig();
    $controller = makeMessageController(messages: $messages, spaces: $spaces, auth: $auth, validator: $validator, config: $config);

    $response = $controller->edit(id: 1, request: makeEditMessageRequest());

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(403)
        ->and($messages->savedMessages)->toBeEmpty();
});

it('deletes a message on DELETE /messages/{id} by the message author', function (): void {
    $user = makeMessageUser(id: 1);
    $message = makeTestMessage(id: 1, spaceId: 1, userId: 1);
    $messages = makeMessageRepository(messages: [$message]);
    $spaces = makeMessageSpaceRepository();
    $auth = makeMessageAuthManager(user: $user);
    $validator = makeMessageValidator();
    $config = makeMessageConfig();
    $controller = makeMessageController(messages: $messages, spaces: $spaces, auth: $auth, validator: $validator, config: $config);

    $response = $controller->delete(id: 1, request: makeDeleteMessageRequest());

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(200)
        ->and($messages->deleteCalled)->toBeTrue()
        ->and($messages->deletedEntity)->toBe($message);
});

it('returns message history as JSON on GET /spaces/{slug}/messages', function (): void {
    $user = makeMessageUser();
    $space = makeMessageSpace();
    $message = makeTestMessage(id: 1, spaceId: 1, userId: 1);
    $messages = makeMessageRepository(messages: [$message]);
    $spaces = makeMessageSpaceRepository(space: $space);
    $auth = makeMessageAuthManager(user: $user);
    $validator = makeMessageValidator();
    $config = makeMessageConfig();
    $controller = makeMessageController(messages: $messages, spaces: $spaces, auth: $auth, validator: $validator, config: $config);

    $response = $controller->history(slug: 'general', request: makeHistoryRequest());

    $decoded = json_decode(json: $response->body(), associative: true);

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(200)
        ->and($response->headers())->toHaveKey('Content-Type')
        ->and($response->headers()['Content-Type'])->toBe('application/json')
        ->and($decoded)->toBeArray()
        ->and($decoded)->toHaveKey('items')
        ->and($decoded['items'])->toHaveCount(1)
        ->and($decoded['items'][0])->toHaveKey('id')
        ->and($decoded['items'][0]['id'])->toBe(1)
        ->and($decoded)->toHaveKey('meta')
        ->and($decoded['meta'])->toHaveKey('has_more')
        ->and($decoded)->toHaveKey('links');
});
