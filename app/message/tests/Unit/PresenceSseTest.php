<?php

declare(strict_types=1);

use App\Space\Entity\Space;
use App\Space\Repository\SpaceRepositoryInterface;
use Marko\Database\Entity\Entity;
use Marko\PubSub\Subscription;
use Marko\PubSub\SubscriberInterface;
use Marko\Sse\StreamingResponse;
use Marko\Config\ConfigRepositoryInterface;
use App\Message\Controller\StreamController;

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

        public function findBy(array $criteria): array
        {
            return [];
        }
    };
}

function makePresenceSubscription(): Subscription
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

function makePresenceSubscriber(?Subscription $subscription = null): SubscriberInterface
{
    return new class ($subscription) implements SubscriberInterface {
        public function __construct(
            private readonly ?Subscription $subscription,
        ) {}

        public function subscribe(string ...$channels): Subscription
        {
            return $this->subscription ?? makePresenceSubscription();
        }

        public function psubscribe(string ...$patterns): Subscription
        {
            return makePresenceSubscription();
        }
    };
}

function makePresenceConfig(array $values = []): ConfigRepositoryInterface
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

function makePresenceController(
    ?SpaceRepositoryInterface $spaces = null,
    ?SubscriberInterface $subscriber = null,
    ?ConfigRepositoryInterface $config = null,
): StreamController {
    return new StreamController(
        spaces: $spaces ?? makePresenceSpaceRepository(space: makePresenceSpace()),
        subscriber: $subscriber ?? makePresenceSubscriber(),
        config: $config ?? makePresenceConfig(),
    );
}

it('renders the members list in the sidebar', function (): void {
    $template = file_get_contents(filename: '/Users/markshust/Sites/markotalk/app/space/resources/views/space/show.latte');

    expect($template)
        ->toContain('id="member-list"')
        ->and($template)->toContain('class="member-list"')
        ->and($template)->toContain('{foreach $members as $member}')
        ->and($template)->toContain('data-user-id="{$member->id}"')
        ->and($template)->toContain('{$member->displayName ?? $member->username}');
});

it('shows online indicator for users with recent activity', function (): void {
    $template = file_get_contents(filename: '/Users/markshust/Sites/markotalk/app/space/resources/views/space/show.latte');

    expect($template)
        ->toContain('class="member-indicator')
        ->and($template)->toContain('is-online')
        ->and($template)->toContain('is-offline')
        ->and($template)->toContain('$onlineUserIds');
});

it('updates member status via JS when presence events arrive', function (): void {
    $jsFile = '/Users/markshust/Sites/markotalk/public/js/app.js';

    expect(file_exists(filename: $jsFile))->toBeTrue();

    $js = file_get_contents(filename: $jsFile);

    expect($js)
        ->toContain("case 'presence'")
        ->and($js)->toContain('member-item')
        ->and($js)->toContain('is-online')
        ->and($js)->toContain('is-offline');
});

it('returns a StreamingResponse for the stream endpoint', function (): void {
    $controller = makePresenceController();

    $response = $controller->stream(slug: 'general');

    expect($response)->toBeInstanceOf(StreamingResponse::class);
});
