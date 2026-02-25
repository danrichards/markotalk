<?php

declare(strict_types=1);

use App\Message\Entity\Message;
use App\Message\Event\MessageCreatedEvent;
use App\Notification\Observer\NotificationStreamObserver;
use App\Space\Entity\SpaceMembership;
use App\Space\Repository\SpaceMembershipRepositoryInterface;
use Marko\Core\Attributes\Observer;
use Psr\SimpleCache\CacheInterface;

function makeCache(): CacheInterface
{
    return new class implements CacheInterface {
        /** @var array<string, mixed> */
        public array $storage = [];

        /** @var array<string> */
        public array $setCalls = [];

        public function get(
            string $key,
            mixed $default = null,
        ): mixed {
            return $this->storage[$key] ?? $default;
        }

        public function set(
            string $key,
            mixed $value,
            null|int|\DateInterval $ttl = null,
        ): bool {
            $this->storage[$key] = $value;
            $this->setCalls[] = $key;

            return true;
        }

        public function delete(string $key): bool
        {
            unset($this->storage[$key]);

            return true;
        }

        public function clear(): bool
        {
            $this->storage = [];

            return true;
        }

        /** @param iterable<string> $keys */
        public function getMultiple(
            iterable $keys,
            mixed $default = null,
        ): iterable {
            $result = [];

            foreach ($keys as $key) {
                $result[$key] = $this->storage[$key] ?? $default;
            }

            return $result;
        }

        /**
         * @param iterable<string, mixed> $values
         */
        public function setMultiple(
            iterable $values,
            null|int|\DateInterval $ttl = null,
        ): bool {
            foreach ($values as $key => $value) {
                $this->storage[$key] = $value;
            }

            return true;
        }

        /** @param iterable<string> $keys */
        public function deleteMultiple(iterable $keys): bool
        {
            foreach ($keys as $key) {
                unset($this->storage[$key]);
            }

            return true;
        }

        public function has(string $key): bool
        {
            return isset($this->storage[$key]);
        }
    };
}

function makeMembershipRepository(array $memberships = []): SpaceMembershipRepositoryInterface
{
    return new class ($memberships) implements SpaceMembershipRepositoryInterface {
        public function __construct(
            private array $memberships,
        ) {}

        public function find(int $id): ?SpaceMembership
        {
            return null;
        }

        public function findOrFail(int $id): SpaceMembership
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

        public function findOneBy(array $criteria): ?SpaceMembership
        {
            return null;
        }

        public function save(\Marko\Database\Entity\Entity $entity): void {}

        public function delete(\Marko\Database\Entity\Entity $entity): void {}

        public function findByUserAndSpace(
            int $userId,
            int $spaceId,
        ): ?SpaceMembership {
            return null;
        }

        public function findAllForUser(
            int $userId,
        ): array {
            return [];
        }

        public function findAllForSpace(
            int $spaceId,
        ): array {
            return $this->memberships;
        }

        public function countUnread(
            int $userId,
            int $spaceId,
        ): int {
            return 0;
        }

        public function updateLastReadMessageId(
            SpaceMembership $membership,
            int $messageId,
        ): void {}
    };
}

function makeMessage(
    int $id = 1,
    int $spaceId = 10,
    int $userId = 5,
): Message {
    return new Message(
        id: $id,
        spaceId: $spaceId,
        userId: $userId,
        body: 'Hello world',
        bodyHtml: '<p>Hello world</p>',
        isPinned: false,
        editedAt: null,
        createdAt: new DateTimeImmutable(),
    );
}

function makeStreamObserverMembership(
    int $userId,
    int $spaceId = 10,
): SpaceMembership {
    return new SpaceMembership(
        id: $userId * 100,
        userId: $userId,
        spaceId: $spaceId,
        lastReadMessageId: null,
        joinedAt: new DateTimeImmutable(),
    );
}

it('listens for MessageCreatedEvent', function (): void {
    $cache = makeCache();
    $memberships = makeMembershipRepository();
    $observer = new NotificationStreamObserver(cache: $cache, memberships: $memberships);

    expect($observer)->toBeInstanceOf(NotificationStreamObserver::class);
});

it('uses #[Observer(event: MessageCreatedEvent::class)]', function (): void {
    $reflection = new ReflectionClass(NotificationStreamObserver::class);
    $attributes = $reflection->getAttributes(name: Observer::class);

    expect($attributes)->toHaveCount(1);

    $observerAttr = $attributes[0]->newInstance();

    expect($observerAttr->event)->toBe(MessageCreatedEvent::class);
});

it('identifies users who should receive notification count updates', function (): void {
    $cache = makeCache();
    $message = makeMessage();
    $authorMembership = makeStreamObserverMembership(userId: 5);
    $otherMembership1 = makeStreamObserverMembership(userId: 6);
    $otherMembership2 = makeStreamObserverMembership(userId: 7);
    $memberships = makeMembershipRepository(memberships: [$authorMembership, $otherMembership1, $otherMembership2]);
    $observer = new NotificationStreamObserver(cache: $cache, memberships: $memberships);

    $event = new MessageCreatedEvent(message: $message);
    $observer->handle(event: $event);

    // Author (userId=5) should NOT have a notification flag set
    expect($cache->has(key: 'notification_pending:5'))->toBeFalse()
        // Other members SHOULD have flags set
        ->and($cache->has(key: 'notification_pending:6'))->toBeTrue()
        ->and($cache->has(key: 'notification_pending:7'))->toBeTrue();
});

it('stores notification state for SSE heartbeat pickup', function (): void {
    $cache = makeCache();
    $message = makeMessage();
    $member = makeStreamObserverMembership(userId: 6);
    $memberships = makeMembershipRepository(memberships: [$member]);
    $observer = new NotificationStreamObserver(cache: $cache, memberships: $memberships);

    $event = new MessageCreatedEvent(message: $message);
    $observer->handle(event: $event);

    $key = 'notification_pending:6';

    expect($cache->get(key: $key))->toBeTrue()
        ->and($cache->setCalls)->toContain($key);
});
