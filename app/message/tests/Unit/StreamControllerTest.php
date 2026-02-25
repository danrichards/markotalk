<?php

declare(strict_types=1);

use App\Message\Controller\StreamController;
use App\Message\Entity\Message;
use App\Message\Repository\MessageRepositoryInterface;
use App\Space\Entity\Space;
use App\Space\Repository\SpaceRepositoryInterface;
use App\User\Entity\User;
use App\User\Service\PresenceTrackerInterface;
use Marko\Authentication\AuthManager;
use Marko\Authentication\AuthenticatableInterface;
use Marko\Database\Entity\Entity;
use Marko\Pagination\CursorPaginator;
use Marko\Sse\StreamingResponse;
use Marko\Routing\Http\Request;

function makeStreamRequest(int $lastEventId = 0): Request
{
    $server = [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/spaces/general/stream',
    ];

    if ($lastEventId > 0) {
        $server['HTTP_LAST_EVENT_ID'] = (string) $lastEventId;
    }

    return new Request(server: $server);
}

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
            throw new RuntimeException('Not implemented');
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

        public function findBy(array $criteria): array
        {
            return [];
        }
    };
}

function makeStreamMessageRepository(array $messages = []): MessageRepositoryInterface
{
    return new class ($messages) implements MessageRepositoryInterface {
        public function __construct(
            private array $messages,
        ) {}

        public function findBySpace(int $spaceId, int $limit = 50): array
        {
            return $this->messages;
        }

        public function findBySpaceSince(int $spaceId, int $sinceId): array
        {
            return array_values(array_filter(
                $this->messages,
                fn (Message $m) => $m->id !== null && $m->id > $sinceId,
            ));
        }

        public function find(int $id): ?Entity
        {
            return null;
        }

        public function findOrFail(int $id): Entity
        {
            throw new RuntimeException('Not implemented');
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

        public function findBy(array $criteria): array
        {
            return [];
        }

        public function findPaginated(int $spaceId, int $perPage = 50, ?string $cursor = null): CursorPaginator
        {
            return new CursorPaginator(
                items: array_values(array_filter(
                    $this->messages,
                    fn (Message $m) => $m->spaceId === $spaceId,
                )),
                perPage: $perPage,
            );
        }
    };
}

function makeStreamAuthManager(?AuthenticatableInterface $user = null): AuthManager
{
    return new class ($user) extends AuthManager {
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

function makeStreamAuthUser(): AuthenticatableInterface
{
    return new class implements AuthenticatableInterface {
        public function getAuthIdentifier(): int|string
        {
            return 1;
        }

        public function getAuthIdentifierName(): string
        {
            return 'id';
        }

        public function getAuthPassword(): string
        {
            return 'hashed_password';
        }

        public function getRememberToken(): ?string
        {
            return null;
        }

        public function setRememberToken(?string $token): void {}

        public function getRememberTokenName(): string
        {
            return 'remember_token';
        }
    };
}

function makeStreamPresenceTracker(): PresenceTrackerInterface
{
    return new class () implements PresenceTrackerInterface {
        public function updateLastSeen(User $user): void {}

        public function isOnline(User $user): bool
        {
            return false;
        }

        public function getOnlineUsers(): array
        {
            return [];
        }
    };
}

function makeStreamController(
    ?SpaceRepositoryInterface $spaces = null,
    ?MessageRepositoryInterface $messages = null,
    ?PresenceTrackerInterface $presence = null,
    array $config = [],
): StreamController {
    $defaultConfig = [
        'sse_poll_interval' => 1,
        'sse_heartbeat_interval' => 15,
        'sse_timeout' => 300,
    ];

    return new StreamController(
        spaces: $spaces ?? makeStreamSpaceRepository(makeStreamSpace()),
        messages: $messages ?? makeStreamMessageRepository(),
        presence: $presence ?? makeStreamPresenceTracker(),
        config: array_merge($defaultConfig, $config),
    );
}

it('returns a StreamingResponse with text/event-stream content type', function (): void {
    $controller = makeStreamController();

    $response = $controller->stream(slug: 'general', request: makeStreamRequest());

    expect($response)->toBeInstanceOf(StreamingResponse::class)
        ->and($response->headers())->toHaveKey('Content-Type')
        ->and($response->headers()['Content-Type'])->toBe('text/event-stream');
});

it('uses SseStream with config-driven poll interval and timeout', function (): void {
    $controller = makeStreamController(config: [
        'sse_poll_interval' => 3,
        'sse_heartbeat_interval' => 30,
        'sse_timeout' => 600,
    ]);

    $response = $controller->stream(slug: 'general', request: makeStreamRequest());

    expect($response)->toBeInstanceOf(StreamingResponse::class);

    $streamProp = (new ReflectionClass($response))->getProperty('stream');
    $stream = $streamProp->getValue($response);

    expect($stream)->toBeInstanceOf(\Marko\Sse\SseStream::class);

    $pollProp = (new ReflectionClass($stream))->getProperty('pollInterval');
    $heartbeatProp = (new ReflectionClass($stream))->getProperty('heartbeatInterval');
    $timeoutProp = (new ReflectionClass($stream))->getProperty('timeout');

    expect($pollProp->getValue($stream))->toBe(3)
        ->and($heartbeatProp->getValue($stream))->toBe(30)
        ->and($timeoutProp->getValue($stream))->toBe(600);
});

it('queries messages newer than the Last-Event-ID', function (): void {
    $messages = [
        new Message(
            id: 5,
            spaceId: 1,
            userId: 1,
            body: 'Message 5',
            bodyHtml: '<p>Message 5</p>',
            isPinned: false,
            editedAt: null,
            createdAt: new DateTimeImmutable('2026-02-24 00:00:00'),
        ),
    ];
    $messageRepository = makeStreamMessageRepository($messages);
    $space = makeStreamSpace(id: 1, slug: 'general');
    $spaceRepository = makeStreamSpaceRepository($space);
    $controller = makeStreamController(
        spaces: $spaceRepository,
        messages: $messageRepository,
    );

    // Send request with Last-Event-ID: 3, so only messages with id > 3 should be returned
    $request = makeStreamRequest(lastEventId: 3);
    $response = $controller->stream(slug: 'general', request: $request);

    expect($response)->toBeInstanceOf(StreamingResponse::class);

    // Extract the data provider from the SseStream
    $streamProp = (new ReflectionClass($response))->getProperty('stream');
    $sseStream = $streamProp->getValue($response);

    $providerProp = (new ReflectionClass($sseStream))->getProperty('dataProvider');
    $dataProvider = $providerProp->getValue($sseStream);

    $events = $dataProvider();

    // Message 5 has id > 3, so it should be returned as an event
    $messageEvents = array_values(array_filter(
        $events,
        fn (\Marko\Sse\SseEvent $event): bool => $event->event === 'message',
    ));

    expect($messageEvents)->toHaveCount(1);
});

it('formats new messages as SseEvent with message HTML as data', function (): void {
    $messages = [
        new Message(
            id: 10,
            spaceId: 1,
            userId: 1,
            body: 'Hello **world**',
            bodyHtml: '<p>Hello <strong>world</strong></p>',
            isPinned: false,
            editedAt: null,
            createdAt: new DateTimeImmutable('2026-02-24 00:00:00'),
        ),
    ];
    $messageRepository = makeStreamMessageRepository($messages);
    $space = makeStreamSpace(id: 1, slug: 'general');
    $spaceRepository = makeStreamSpaceRepository($space);
    $controller = makeStreamController(
        spaces: $spaceRepository,
        messages: $messageRepository,
    );

    $response = $controller->stream(slug: 'general', request: makeStreamRequest());

    $streamProp = (new ReflectionClass($response))->getProperty('stream');
    $sseStream = $streamProp->getValue($response);

    $providerProp = (new ReflectionClass($sseStream))->getProperty('dataProvider');
    $dataProvider = $providerProp->getValue($sseStream);

    $events = $dataProvider();

    $messageEvents = array_values(array_filter(
        $events,
        fn (\Marko\Sse\SseEvent $event): bool => $event->event === 'message',
    ));

    expect($messageEvents)->toHaveCount(1);

    $event = $messageEvents[0];

    expect($event)->toBeInstanceOf(\Marko\Sse\SseEvent::class)
        ->and($event->data)->toBe('<p>Hello <strong>world</strong></p>')
        ->and($event->event)->toBe('message')
        ->and($event->id)->toBe(10);
});

it('sends heartbeat pings at the configured interval', function (): void {
    $controller = makeStreamController(config: [
        'sse_poll_interval' => 1,
        'sse_heartbeat_interval' => 20,
        'sse_timeout' => 300,
    ]);

    $response = $controller->stream(slug: 'general', request: makeStreamRequest());

    $streamProp = (new ReflectionClass($response))->getProperty('stream');
    $sseStream = $streamProp->getValue($response);

    $heartbeatProp = (new ReflectionClass($sseStream))->getProperty('heartbeatInterval');

    expect($heartbeatProp->getValue($sseStream))->toBe(20);
});

it('requires authentication', function (): void {
    $method = new ReflectionMethod(StreamController::class, 'stream');
    $attributes = $method->getAttributes(\Marko\Routing\Attributes\Get::class);

    expect($attributes)->toHaveCount(1);

    $getAttribute = $attributes[0]->newInstance();

    expect($getAttribute->middleware)->toContain(\Marko\Authentication\Middleware\AuthMiddleware::class);
});
