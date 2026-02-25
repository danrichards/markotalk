<?php

declare(strict_types=1);

use App\Message\Controller\StreamController;
use App\Message\Repository\MessageRepositoryInterface;
use App\Space\Entity\Space;
use App\Space\Repository\SpaceRepositoryInterface;
use App\User\Entity\User;
use App\User\Enum\UserRole;
use App\User\Service\PresenceTrackerInterface;
use Marko\Authentication\AuthManager;
use Marko\Authentication\AuthenticatableInterface;
use Marko\Database\Entity\Entity;
use Marko\Pagination\CursorPaginator;
use Marko\Sse\SseEvent;
use Marko\Sse\StreamingResponse;
use Marko\Routing\Http\Request;
use App\Message\Entity\Message;

function makePresenceRequest(int $lastEventId = 0): Request
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

function makePresenceSpace(int $id = 1, string $slug = 'general'): Space
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

function makePresenceSpaceRepository(?Space $space = null): SpaceRepositoryInterface
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

function makePresenceMessageRepository(array $messages = []): MessageRepositoryInterface
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

function makePresenceAuthManager(?AuthenticatableInterface $user = null): AuthManager
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

function makePresenceAuthUser(): AuthenticatableInterface
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

function makeSsePresenceTracker(array $onlineUsers = []): PresenceTrackerInterface
{
    return new class ($onlineUsers) implements PresenceTrackerInterface {
        public function __construct(
            private readonly array $onlineUsers,
        ) {}

        public function updateLastSeen(User $user): void {}

        public function isOnline(User $user): bool
        {
            foreach ($this->onlineUsers as $onlineUser) {
                if ($onlineUser->id === $user->id) {
                    return true;
                }
            }

            return false;
        }

        public function getOnlineUsers(): array
        {
            return $this->onlineUsers;
        }
    };
}

function makeOnlineUser(int $id = 1, string $username = 'alice'): User
{
    return new User(
        id: $id,
        username: $username,
        email: $username . '@example.com',
        password: 'hashed',
        displayName: ucfirst($username),
        avatarUrl: null,
        role: UserRole::User,
        isBanned: false,
        lastSeenAt: new DateTimeImmutable(),
        rememberToken: null,
        createdAt: new DateTimeImmutable('2026-02-24 00:00:00'),
        updatedAt: new DateTimeImmutable('2026-02-24 00:00:00'),
    );
}

function makePresenceController(
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
        spaces: $spaces ?? makePresenceSpaceRepository(makePresenceSpace()),
        messages: $messages ?? makePresenceMessageRepository(),
        presence: $presence ?? makeSsePresenceTracker(),
        config: array_merge($defaultConfig, $config),
    );
}

it('includes presence data in SSE heartbeat frames', function (): void {
    $onlineUsers = [
        makeOnlineUser(id: 1, username: 'alice'),
        makeOnlineUser(id: 2, username: 'bob'),
    ];
    $presenceTracker = makeSsePresenceTracker(onlineUsers: $onlineUsers);

    $controller = makePresenceController(presence: $presenceTracker);

    $response = $controller->stream(slug: 'general', request: makePresenceRequest());

    expect($response)->toBeInstanceOf(StreamingResponse::class);

    $streamProp = (new ReflectionClass($response))->getProperty('stream');
    $sseStream = $streamProp->getValue($response);

    $providerProp = (new ReflectionClass($sseStream))->getProperty('dataProvider');
    $dataProvider = $providerProp->getValue($sseStream);

    $events = $dataProvider();

    $presenceEvents = array_filter(
        $events,
        fn (SseEvent $event): bool => $event->event === 'presence',
    );

    expect($presenceEvents)->not->toBeEmpty();
});

it('sends a \'presence\' event type with online user IDs', function (): void {
    $onlineUsers = [
        makeOnlineUser(id: 3, username: 'carol'),
        makeOnlineUser(id: 7, username: 'dave'),
    ];
    $presenceTracker = makeSsePresenceTracker(onlineUsers: $onlineUsers);

    $controller = makePresenceController(presence: $presenceTracker);

    $response = $controller->stream(slug: 'general', request: makePresenceRequest());

    $streamProp = (new ReflectionClass($response))->getProperty('stream');
    $sseStream = $streamProp->getValue($response);

    $providerProp = (new ReflectionClass($sseStream))->getProperty('dataProvider');
    $dataProvider = $providerProp->getValue($sseStream);

    $events = $dataProvider();

    $presenceEvents = array_values(array_filter(
        $events,
        fn (SseEvent $event): bool => $event->event === 'presence',
    ));

    expect($presenceEvents)->not->toBeEmpty();

    $presenceEvent = $presenceEvents[0];

    expect($presenceEvent->event)->toBe('presence')
        ->and($presenceEvent->data)->toBeArray()
        ->and($presenceEvent->data)->toContain(3)
        ->and($presenceEvent->data)->toContain(7);
});

it('renders the members list in the sidebar', function (): void {
    $template = file_get_contents('/Users/markshust/Sites/markotalk/app/space/resources/views/space/show.latte');

    expect($template)
        ->toContain('id="member-list"')
        ->and($template)->toContain('class="member-list"')
        ->and($template)->toContain('{foreach $members as $member}')
        ->and($template)->toContain('data-user-id="{$member->id}"')
        ->and($template)->toContain('{$member->displayName ?? $member->username}');
});

it('shows online indicator for users with recent activity', function (): void {
    $template = file_get_contents('/Users/markshust/Sites/markotalk/app/space/resources/views/space/show.latte');

    expect($template)
        ->toContain('class="member-indicator')
        ->and($template)->toContain('is-online')
        ->and($template)->toContain('is-offline')
        ->and($template)->toContain('$member->isOnline');
});

it('updates member status via JS when presence events arrive', function (): void {
    $jsFile = '/Users/markshust/Sites/markotalk/public/js/app.js';

    expect(file_exists($jsFile))->toBeTrue();

    $js = file_get_contents($jsFile);

    expect($js)
        ->toContain("addEventListener('presence'")
        ->and($js)->toContain('member-item')
        ->and($js)->toContain('is-online')
        ->and($js)->toContain('is-offline');
});
