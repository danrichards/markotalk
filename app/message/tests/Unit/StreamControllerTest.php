<?php

declare(strict_types=1);

use App\Message\Controller\StreamController;
use App\Message\Repository\MessageRepositoryInterface;
use App\Space\Entity\Space;
use App\Space\Repository\SpaceRepositoryInterface;
use App\User\Middleware\PresenceMiddleware;
use App\User\Repository\UserRepositoryInterface;
use Marko\Authentication\AuthManager;
use Marko\Authentication\Middleware\AuthMiddleware;
use Marko\Config\ConfigRepositoryInterface;
use Marko\Database\Entity\Entity;
use Marko\PubSub\Subscription;
use Marko\PubSub\SubscriberInterface;
use Marko\Routing\Attributes\Get;
use Marko\Sse\SseStream;
use Marko\Sse\StreamingResponse;

function makeStreamSpace(int $id = 1, string $slug = 'general'): Space
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

function makeStreamSpaceRepository(?Space $space = null): SpaceRepositoryInterface
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

        public function find(int $id): ?Entity
        {
            return null;
        }

        public function findOrFail(int $id): Entity
        {
            throw new RuntimeException(message: 'Not implemented');
        }

        public function save(Entity $entity): void {}

        public function delete(Entity $entity): void {}

        public function findAll(): array
        {
            return [];
        }

        public function findOneBy(array $criteria): ?Entity
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

function makeStreamSubscription(): Subscription
{
    return new class () implements Subscription {
        public function getIterator(): Generator
        {
            return;
            yield;
        }

        public function cancel(): void {}
    };
}

function makeStreamSubscriber(
    ?Subscription $subscription = null,
    ?string &$subscribedChannel = null,
): SubscriberInterface {
    return new class ($subscription, $subscribedChannel) implements SubscriberInterface {
        public function __construct(
            private readonly ?Subscription $subscription,
            /** @noinspection PhpPropertyOnlyWrittenInspection - Reference property modifies external variable */
            private mixed &$subscribedChannel,
        ) {}

        public function subscribe(string ...$channels): Subscription
        {
            $this->subscribedChannel = implode(separator: ',', array: $channels);

            return $this->subscription ?? makeStreamSubscription();
        }

        public function psubscribe(string ...$patterns): Subscription
        {
            return makeStreamSubscription();
        }
    };
}

function makeStreamConfig(array $values = []): ConfigRepositoryInterface
{
    $defaults = [
        'markotalk.sse_timeout' => 300,
    ];
    $merged = array_merge($defaults, $values);

    return new class ($merged) implements ConfigRepositoryInterface {
        public function __construct(
            private readonly array $values,
        ) {}

        public function get(string $key, ?string $scope = null): mixed
        {
            return $this->values[$key] ?? null;
        }

        public function has(string $key, ?string $scope = null): bool
        {
            return isset($this->values[$key]);
        }

        public function getString(string $key, ?string $scope = null): string
        {
            return (string) ($this->values[$key] ?? '');
        }

        public function getInt(string $key, ?string $scope = null): int
        {
            return (int) ($this->values[$key] ?? 0);
        }

        public function getBool(string $key, ?string $scope = null): bool
        {
            return (bool) ($this->values[$key] ?? false);
        }

        public function getFloat(string $key, ?string $scope = null): float
        {
            return (float) ($this->values[$key] ?? 0.0);
        }

        public function getArray(string $key, ?string $scope = null): array
        {
            return (array) ($this->values[$key] ?? []);
        }

        public function all(?string $scope = null): array
        {
            return $this->values;
        }

        public function withScope(string $scope): ConfigRepositoryInterface
        {
            return $this;
        }
    };
}

function makeStreamController(
    ?SpaceRepositoryInterface $spaces = null,
    ?SubscriberInterface $subscriber = null,
    ?ConfigRepositoryInterface $config = null,
): StreamController {
    return new StreamController(
        spaceRepository: $spaces ?? makeStreamSpaceRepository(space: makeStreamSpace()),
        subscriber: $subscriber ?? makeStreamSubscriber(),
        config: $config ?? makeStreamConfig(),
    );
}

it('returns a StreamingResponse with text/event-stream content type', function (): void {
    $controller = makeStreamController();

    $response = $controller->stream(slug: 'general');

    expect($response)->toBeInstanceOf(StreamingResponse::class)
        ->and($response->headers())->toHaveKey('Content-Type')
        ->and($response->headers()['Content-Type'])->toBe('text/event-stream');
});

it('creates an SseStream with a subscription instead of a dataProvider', function (): void {
    $subscription = makeStreamSubscription();
    $subscriber = makeStreamSubscriber(subscription: $subscription);
    $controller = makeStreamController(subscriber: $subscriber);

    $response = $controller->stream(slug: 'general');

    $streamProp = (new ReflectionClass(objectOrClass: $response))->getProperty(name: 'stream');
    $sseStream = $streamProp->getValue(object: $response);

    expect($sseStream)->toBeInstanceOf(SseStream::class);

    $subscriptionProp = (new ReflectionClass(objectOrClass: $sseStream))->getProperty(name: 'subscription');

    expect($subscriptionProp->getValue(object: $sseStream))->toBe($subscription);
});

it('subscribes to the space:{slug} channel', function (): void {
    $subscribedChannel = null;
    $subscriber = makeStreamSubscriber(subscribedChannel: $subscribedChannel);
    $controller = makeStreamController(subscriber: $subscriber);

    $controller->stream(slug: 'general');

    expect($subscribedChannel)->toBe('space:general');
});

it('uses the configured sse_timeout for the stream', function (): void {
    $config = makeStreamConfig(values: ['markotalk.sse_timeout' => 600]);
    $controller = makeStreamController(config: $config);

    $response = $controller->stream(slug: 'general');

    $streamProp = (new ReflectionClass(objectOrClass: $response))->getProperty(name: 'stream');
    $sseStream = $streamProp->getValue(object: $response);

    $timeoutProp = (new ReflectionClass(objectOrClass: $sseStream))->getProperty(name: 'timeout');

    expect($timeoutProp->getValue(object: $sseStream))->toBe(600);
});

it('requires AuthMiddleware and PresenceMiddleware', function (): void {
    $method = new ReflectionMethod(objectOrMethod: StreamController::class, method: 'stream');
    $attributes = $method->getAttributes(name: Get::class);

    expect($attributes)->toHaveCount(1);

    $getAttribute = $attributes[0]->newInstance();

    expect($getAttribute->middleware)->toContain(AuthMiddleware::class)
        ->and($getAttribute->middleware)->toContain(PresenceMiddleware::class);
});

it('does not inject MessageRepositoryInterface, UserRepositoryInterface, or AuthManager', function (): void {
    $constructor = new ReflectionMethod(objectOrMethod: StreamController::class, method: '__construct');
    $params = $constructor->getParameters();

    $paramTypes = array_map(
        callback: fn (ReflectionParameter $p): string => (string) $p->getType(),
        array: $params,
    );

    expect($paramTypes)->not->toContain(MessageRepositoryInterface::class)
        ->and($paramTypes)->not->toContain(UserRepositoryInterface::class)
        ->and($paramTypes)->not->toContain(AuthManager::class);
});
