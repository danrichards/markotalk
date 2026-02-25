<?php

declare(strict_types=1);

use App\Message\Entity\Message;
use App\Message\Event\MentionDetectedEvent;
use App\Message\Observer\MentionNotificationObserver;
use App\Notification\Notification\MentionNotification;
use App\Space\Entity\Space;
use App\Space\Repository\SpaceRepositoryInterface;
use App\User\Entity\User;
use App\User\Enum\UserRole;
use App\User\Repository\UserRepositoryInterface;
use Marko\Core\Attributes\Observer;
use Marko\Database\Entity\Entity as DatabaseEntity;
use Marko\Notification\Contracts\NotifiableInterface;
use Marko\Notification\Contracts\NotificationInterface;
use Marko\Notification\NotificationSender;

// Helper factories

function makeMentionUser(int $id = 1, string $username = 'johndoe'): User
{
    return new User(
        id: $id,
        username: $username,
        email: $username . '@example.com',
        password: 'hashed_password',
        displayName: ucfirst(string: $username),
        avatarUrl: null,
        role: UserRole::User,
        isBanned: false,
        lastSeenAt: null,
        rememberToken: null,
        createdAt: new DateTimeImmutable('2026-02-24 00:00:00'),
        updatedAt: new DateTimeImmutable('2026-02-24 00:00:00'),
    );
}

function makeMentionMessage(int $id = 1, int $spaceId = 1, int $userId = 1, string $body = 'Hello @johndoe'): Message
{
    return new Message(
        id: $id,
        spaceId: $spaceId,
        userId: $userId,
        body: $body,
        bodyHtml: '<p>' . htmlspecialchars(string: $body) . '</p>',
        isPinned: false,
        editedAt: null,
        createdAt: new DateTimeImmutable('2026-02-24 00:00:00'),
    );
}

function makeMentionSpace(int $id = 1, string $slug = 'general'): Space
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

function makeMentionUserRepository(?User $user = null): UserRepositoryInterface
{
    return new readonly class ($user) implements UserRepositoryInterface {
        public function __construct(
            private ?User $user,
        ) {}

        public function findByUsername(string $username): ?User
        {
            return $this->user;
        }

        public function findByEmail(string $email): ?User
        {
            return null;
        }

        public function findByRememberToken(int $userId, string $token): ?User
        {
            return null;
        }

        public function updateRememberToken(User $user, ?string $token): void {}

        public function find(int $id): ?DatabaseEntity
        {
            return $this->user;
        }

        public function findOrFail(int $id): DatabaseEntity
        {
            return $this->user ?? throw new RuntimeException(message: 'Not found');
        }

        public function findAll(): array
        {
            return $this->user !== null ? [$this->user] : [];
        }

        public function findBy(array $criteria): array
        {
            return $this->user !== null ? [$this->user] : [];
        }

        public function findOneBy(array $criteria): ?DatabaseEntity
        {
            return $this->user;
        }

        public function save(DatabaseEntity $entity): void {}

        public function delete(DatabaseEntity $entity): void {}
    };
}

function makeMentionSpaceRepository(?Space $space = null): SpaceRepositoryInterface
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

        public function findBy(array $criteria): array
        {
            return $this->space !== null ? [$this->space] : [];
        }

        public function findOneBy(array $criteria): ?DatabaseEntity
        {
            return $this->space;
        }

        public function save(DatabaseEntity $entity): void {}

        public function delete(DatabaseEntity $entity): void {}
    };
}

/**
 * @param array<array{notifiable: NotifiableInterface, notification: NotificationInterface}> $sent
 */
function makeMentionNotificationSender(array &$sent): NotificationSender
{
    /** @noinspection PhpMissingParentConstructorInspection - Test stub intentionally skips parent */
    return new class ($sent) extends NotificationSender {
        /**
         * @param array<array{notifiable: NotifiableInterface, notification: NotificationInterface}> $sent
         *
         * @noinspection PhpMissingParentConstructorInspection
         */
        public function __construct(
            /** @noinspection PhpPropertyOnlyWrittenInspection - Reference property modifies external variable */
            private array &$sent,
        ) {
            // Skip parent constructor - we don't need manager or queue
        }

        public function send(
            NotifiableInterface|array $notifiables,
            NotificationInterface $notification,
        ): void {
            $notifiables = is_array(value: $notifiables) ? $notifiables : [$notifiables];

            foreach ($notifiables as $notifiable) {
                $this->sent[] = [
                    'notifiable' => $notifiable,
                    'notification' => $notification,
                ];
            }
        }
    };
}

// Tests

it('sends a MentionNotification when a valid user is mentioned', function (): void {
    $author = makeMentionUser(id: 1, username: 'alice');
    $mentioned = makeMentionUser(id: 2, username: 'johndoe');
    $space = makeMentionSpace(id: 1, slug: 'general');
    $message = makeMentionMessage(id: 5, spaceId: 1, userId: 1, body: 'Hey @johndoe!');

    $users = makeMentionUserRepository(user: $mentioned);
    $spaces = makeMentionSpaceRepository(space: $space);
    $sent = [];
    $sender = makeMentionNotificationSender(sent: $sent);

    $authorRepo = makeMentionUserRepository(user: $author);

    $observer = new MentionNotificationObserver(
        users: $users,
        spaces: $spaces,
        sender: $sender,
        authorRepository: $authorRepo,
    );

    $event = new MentionDetectedEvent(message: $message, username: 'johndoe');
    $observer->handle(event: $event);

    expect($sent)->toHaveCount(1)
        ->and($sent[0]['notifiable'])->toBe($mentioned)
        ->and($sent[0]['notification'])->toBeInstanceOf(MentionNotification::class);
});

it('skips notification when mentioned username does not exist', function (): void {
    $space = makeMentionSpace(id: 1, slug: 'general');
    $message = makeMentionMessage(id: 5, spaceId: 1, userId: 1, body: 'Hey @nonexistent!');

    $users = makeMentionUserRepository(user: null);
    $spaces = makeMentionSpaceRepository(space: $space);
    $sent = [];
    $sender = makeMentionNotificationSender(sent: $sent);
    $author = makeMentionUser(id: 1, username: 'alice');
    $authorRepo = makeMentionUserRepository(user: $author);

    $observer = new MentionNotificationObserver(
        users: $users,
        spaces: $spaces,
        sender: $sender,
        authorRepository: $authorRepo,
    );

    $event = new MentionDetectedEvent(message: $message, username: 'nonexistent');
    $observer->handle(event: $event);

    expect($sent)->toBeEmpty();
});

it('uses #[Observer(event: MentionDetectedEvent::class)]', function (): void {
    $reflection = new ReflectionClass(MentionNotificationObserver::class);
    $attributes = $reflection->getAttributes(Observer::class);
    $attribute = $attributes[0]->newInstance();

    expect($attributes)->toHaveCount(1)
        ->and($attribute->event)->toBe(MentionDetectedEvent::class);
});

it('resolves the mentioned username to a User entity', function (): void {
    $author = makeMentionUser(id: 1, username: 'alice');
    $mentioned = makeMentionUser(id: 2, username: 'bobsmith');
    $space = makeMentionSpace(id: 1, slug: 'dev');
    $message = makeMentionMessage(id: 10, spaceId: 1, userId: 1, body: 'Hey @bobsmith, check this out!');

    $resolvedUsernames = [];
    $usersRepo = new class ($mentioned, $resolvedUsernames) implements UserRepositoryInterface {
        /** @var array<string> */
        public array $resolvedUsernames = [];

        public function __construct(
            private readonly User $mentioned,
            array $resolvedUsernamesRef,
        ) {
            $this->resolvedUsernames = $resolvedUsernamesRef;
        }

        public function findByUsername(string $username): ?User
        {
            $this->resolvedUsernames[] = $username;

            return $this->mentioned;
        }

        public function findByEmail(string $email): ?User
        {
            return null;
        }

        public function findByRememberToken(int $userId, string $token): ?User
        {
            return null;
        }

        public function updateRememberToken(User $user, ?string $token): void {}

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

        public function findBy(array $criteria): array
        {
            return [];
        }

        public function findOneBy(array $criteria): ?DatabaseEntity
        {
            return null;
        }

        public function save(DatabaseEntity $entity): void {}

        public function delete(DatabaseEntity $entity): void {}
    };

    $spaces = makeMentionSpaceRepository(space: $space);
    $sent = [];
    $sender = makeMentionNotificationSender(sent: $sent);
    $authorRepo = makeMentionUserRepository(user: $author);

    $observer = new MentionNotificationObserver(
        users: $usersRepo,
        spaces: $spaces,
        sender: $sender,
        authorRepository: $authorRepo,
    );

    $event = new MentionDetectedEvent(message: $message, username: 'bobsmith');
    $observer->handle(event: $event);

    expect($usersRepo->resolvedUsernames)->toHaveCount(1)
        ->and($usersRepo->resolvedUsernames[0])->toBe('bobsmith')
        ->and($sent)->toHaveCount(1)
        ->and($sent[0]['notifiable'])->toBe($mentioned);
});

it('skips notification when user mentions themselves', function (): void {
    $author = makeMentionUser(id: 1, username: 'alice');
    $space = makeMentionSpace(id: 1, slug: 'general');
    $message = makeMentionMessage(id: 5, spaceId: 1, userId: 1, body: 'Hey @alice!');

    // The mentioned user has the same id as the message author
    $users = makeMentionUserRepository(user: $author);
    $spaces = makeMentionSpaceRepository(space: $space);
    $sent = [];
    $sender = makeMentionNotificationSender(sent: $sent);
    $authorRepo = makeMentionUserRepository(user: $author);

    $observer = new MentionNotificationObserver(
        users: $users,
        spaces: $spaces,
        sender: $sender,
        authorRepository: $authorRepo,
    );

    $event = new MentionDetectedEvent(message: $message, username: 'alice');
    $observer->handle(event: $event);

    expect($sent)->toBeEmpty();
});
