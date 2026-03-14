<?php

declare(strict_types=1);

use App\Message\Controller\MessageController;
use App\Message\Entity\Message;
use App\Message\Entity\Reaction;
use App\Message\Repository\MessageRepositoryInterface;
use App\Message\Repository\ReactionRepositoryInterface;
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
use Marko\Validation\Validation\ValidationErrors;

// Helper factories

function makeReactionUser(int $id = 1): User
{
    return new User(
        id: $id,
        username: 'testuser',
        email: 'test@example.com',
        password: 'hashed_password',
        displayName: 'Test User',
        avatarUrl: null,
        role: UserRole::User,
        isBanned: false,
        lastSeenAt: null,
        rememberToken: null,
        createdAt: new DateTimeImmutable('2026-02-24 00:00:00'),
        updatedAt: new DateTimeImmutable('2026-02-24 00:00:00'),
    );
}

function makeReactionMessage(int $id = 1, int $spaceId = 1, int $userId = 1): Message
{
    return new Message(
        id: $id,
        spaceId: $spaceId,
        userId: $userId,
        body: 'Hello world',
        bodyHtml: '<p>Hello world</p>',
        isPinned: false,
        editedAt: null,
        createdAt: new DateTimeImmutable('2026-02-24 00:00:00'),
    );
}

function makeReactionAuthManager(?AuthenticatableInterface $user = null): AuthManager
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

        public function id(): int|string|null
        {
            return $this->mockUser?->getAuthIdentifier();
        }
    };
}

function makeReactionMessageRepository(array $messages = []): MessageRepositoryInterface
{
    return new readonly class ($messages) implements MessageRepositoryInterface {
        public function __construct(
            private array $messages,
        ) {}

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

        public function save(DatabaseEntity $entity): void {}

        public function delete(DatabaseEntity $entity): void {}

        public function findPaginated(int $spaceId, int $perPage = 50, ?string $cursor = null): CursorPaginator
        {
            return new CursorPaginator(items: [], perPage: $perPage);
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

function makeReactionSpaceRepository(): SpaceRepositoryInterface
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

function makeReactionValidator(): ValidatorInterface
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

function makeReactionConfig(): ConfigRepositoryInterface
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

/**
 * @param array<array{emoji: string, count: int, user_reacted: bool}> $grouped
 */
function makeReactionRepository(
    bool $existingReaction = false,
    array $grouped = [],
): ReactionRepositoryInterface {
    return new class ($existingReaction, $grouped) implements ReactionRepositoryInterface {
        public bool $addCalled = false;
        public bool $removeCalled = false;
        public int $addMessageId = 0;
        public int $addUserId = 0;
        public string $addEmoji = '';
        public int $removeMessageId = 0;
        public int $removeUserId = 0;
        public string $removeEmoji = '';

        public function __construct(
            private readonly bool $existingReaction,
            private readonly array $grouped,
        ) {}

        public function findByMessage(int $messageId): array
        {
            return [];
        }

        public function findGrouped(int $messageId, int $userId): array
        {
            return $this->grouped;
        }

        public function add(int $messageId, int $userId, string $emoji): void
        {
            $this->addCalled = true;
            $this->addMessageId = $messageId;
            $this->addUserId = $userId;
            $this->addEmoji = $emoji;
        }

        public function remove(int $messageId, int $userId, string $emoji): void
        {
            $this->removeCalled = true;
            $this->removeMessageId = $messageId;
            $this->removeUserId = $userId;
            $this->removeEmoji = $emoji;
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
            if ($this->existingReaction) {
                return new Reaction(
                    id: 1,
                    messageId: (int) ($criteria['message_id'] ?? 0),
                    userId: (int) ($criteria['user_id'] ?? 0),
                    emoji: (string) ($criteria['emoji'] ?? ''),
                );
            }

            return null;
        }

        public function existsBy(array $criteria): bool { return $this->findOneBy(criteria: $criteria) !== null; }

        public function findBy(array $criteria): array
        {
            return [];
        }
    };
}

function makeReactionController(
    MessageRepositoryInterface $messages,
    ReactionRepositoryInterface $reactionRepository,
    AuthManager $auth,
): MessageController {
    return new MessageController(
        messageRepository: $messages,
        spaceRepository: makeReactionSpaceRepository(),
        auth: $auth,
        validator: makeReactionValidator(),
        configRepository: makeReactionConfig(),
        reactionRepository: $reactionRepository,
    );
}

function makeReactRequest(int $id = 1, string $emoji = '👍'): Request
{
    return new Request(
        server: ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => "/messages/$id/reactions"],
        post: ['emoji' => $emoji],
    );
}

// Tests

it('updates reactions via fetch without page reload', function (): void {
    $jsFile = dirname(path: __DIR__, levels: 4) . '/public/js/app.js';

    expect(file_exists(filename: $jsFile))->toBeTrue();

    $content = file_get_contents(filename: $jsFile);

    expect($content)
        ->toContain('message-reaction')
        ->toContain('emoji-option')
        ->toContain('fetch(')
        ->toContain('/reactions')
        ->toContain('updateReactions');
});

it('has an inline emoji picker UI', function (): void {
    $jsFile = dirname(path: __DIR__, levels: 4) . '/public/js/app.js';

    expect(file_exists(filename: $jsFile))->toBeTrue();

    $content = file_get_contents(filename: $jsFile);

    expect($content)
        ->toContain('emoji-picker')
        ->toContain('emoji-option');
});

it('displays reaction counts under messages', function (): void {
    $jsFile = dirname(path: __DIR__, levels: 4) . '/public/js/app.js';

    expect(file_exists(filename: $jsFile))->toBeTrue();

    $content = file_get_contents(filename: $jsFile);

    expect($content)
        ->toContain('message-reaction')
        ->toContain('updateReactions');
});

it('prevents duplicate reactions (same user, same emoji, same message)', function (): void {
    $user = makeReactionUser(id: 1);
    $message = makeReactionMessage(id: 1);
    $messages = makeReactionMessageRepository(messages: [$message]);
    $reactions = makeReactionRepository(existingReaction: true, grouped: [
        ['emoji' => '👍', 'count' => 0, 'user_reacted' => false],
    ]);
    $auth = makeReactionAuthManager(user: $user);
    $controller = makeReactionController(messages: $messages, reactionRepository: $reactions, auth: $auth);

    // When reaction already exists, it should remove (not add a duplicate)
    $controller->react(id: 1, request: makeReactRequest(emoji: '👍'));

    expect($reactions->addCalled)->toBeFalse()
        ->and($reactions->removeCalled)->toBeTrue();
});

it('removes a reaction from a message', function (): void {
    $user = makeReactionUser(id: 1);
    $message = makeReactionMessage(id: 1);
    $messages = makeReactionMessageRepository(messages: [$message]);
    $reactions = makeReactionRepository(existingReaction: true, grouped: []);
    $auth = makeReactionAuthManager(user: $user);
    $controller = makeReactionController(messages: $messages, reactionRepository: $reactions, auth: $auth);

    $response = $controller->react(id: 1, request: makeReactRequest(emoji: '👍'));

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(200)
        ->and($reactions->removeCalled)->toBeTrue()
        ->and($reactions->removeEmoji)->toBe('👍')
        ->and($reactions->removeMessageId)->toBe(1)
        ->and($reactions->removeUserId)->toBe(1)
        ->and($reactions->addCalled)->toBeFalse();
});

it('adds a reaction to a message', function (): void {
    $user = makeReactionUser(id: 1);
    $message = makeReactionMessage(id: 1);
    $messages = makeReactionMessageRepository(messages: [$message]);
    $reactions = makeReactionRepository(existingReaction: false, grouped: [
        ['emoji' => '👍', 'count' => 1, 'user_reacted' => true],
    ]);
    $auth = makeReactionAuthManager(user: $user);
    $controller = makeReactionController(messages: $messages, reactionRepository: $reactions, auth: $auth);

    $response = $controller->react(id: 1, request: makeReactRequest(emoji: '👍'));

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->statusCode())->toBe(200)
        ->and($reactions->addCalled)->toBeTrue()
        ->and($reactions->addEmoji)->toBe('👍')
        ->and($reactions->addMessageId)->toBe(1)
        ->and($reactions->addUserId)->toBe(1);
});
