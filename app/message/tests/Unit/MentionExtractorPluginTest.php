<?php

declare(strict_types=1);

use App\Message\Entity\Message;
use App\Message\Event\MentionDetectedEvent;
use App\Message\Plugin\MentionExtractorPlugin;
use App\Message\Repository\MessageRepository;
use Marko\Core\Attributes\After;
use Marko\Core\Attributes\Plugin;
use Marko\Core\Event\Event;
use Marko\Core\Event\EventDispatcherInterface;

it('extracts single @mention from message body', function (): void {
    $dispatchedEvents = [];
    $dispatcher = createMentionMockDispatcherWithHistory(dispatchedEvents: $dispatchedEvents);

    $plugin = new MentionExtractorPlugin(dispatcher: $dispatcher);

    $message = createMentionTestMessage(body: 'Hello @johndoe how are you?');

    $plugin->save(result: null, message: $message);

    expect($dispatchedEvents)->toHaveCount(1)
        ->and($dispatchedEvents[0])->toBeInstanceOf(MentionDetectedEvent::class)
        ->and($dispatchedEvents[0]->username)->toBe('johndoe');
});

it('extracts multiple @mentions from message body', function (): void {
    $dispatchedEvents = [];
    $dispatcher = createMentionMockDispatcherWithHistory(dispatchedEvents: $dispatchedEvents);

    $plugin = new MentionExtractorPlugin(dispatcher: $dispatcher);

    $message = createMentionTestMessage(body: 'Hey @alice and @bob, welcome!');

    $plugin->save(result: null, message: $message);

    expect($dispatchedEvents)->toHaveCount(2)
        ->and($dispatchedEvents[0]->username)->toBe('alice')
        ->and($dispatchedEvents[1]->username)->toBe('bob');
});

it('ignores duplicate @mentions in the same message', function (): void {
    $dispatchedEvents = [];
    $dispatcher = createMentionMockDispatcherWithHistory(dispatchedEvents: $dispatchedEvents);

    $plugin = new MentionExtractorPlugin(dispatcher: $dispatcher);

    $message = createMentionTestMessage(body: '@alice said hi, then @alice said bye');

    $plugin->save(result: null, message: $message);

    expect($dispatchedEvents)->toHaveCount(1)
        ->and($dispatchedEvents[0]->username)->toBe('alice');
});

it('dispatches MentionDetectedEvent for each unique mention', function (): void {
    $dispatchedEvents = [];
    $dispatcher = createMentionMockDispatcherWithHistory(dispatchedEvents: $dispatchedEvents);

    $plugin = new MentionExtractorPlugin(dispatcher: $dispatcher);

    $message = createMentionTestMessage(body: 'Hey @alice, @bob_123, and @charlie!');

    $plugin->save(result: null, message: $message);

    expect($dispatchedEvents)->toHaveCount(3);

    $usernames = array_map(callback: fn (MentionDetectedEvent $e): string => $e->username, array: $dispatchedEvents);

    expect($usernames)->toContain('alice')
        ->and($usernames)->toContain('bob_123')
        ->and($usernames)->toContain('charlie');

    foreach ($dispatchedEvents as $event) {
        expect($event)->toBeInstanceOf(MentionDetectedEvent::class)
            ->and($event->message)->toBe($message);
    }
});

it('returns the original result from save', function (): void {
    $dispatcher = createMentionMockDispatcher();

    $plugin = new MentionExtractorPlugin(dispatcher: $dispatcher);

    $message = createMentionTestMessage(body: 'Hello @johndoe');

    $result = $plugin->save(result: 'some-result', message: $message);

    expect($result)->toBe('some-result');
});

it('uses #[After] with save method matching target method name', function (): void {
    $reflection = new ReflectionClass(MentionExtractorPlugin::class);

    $classAttributes = $reflection->getAttributes(Plugin::class);
    $pluginAttribute = $classAttributes[0]->newInstance();

    $method = $reflection->getMethod('save');
    $afterAttributes = $method->getAttributes(After::class);
    $afterAttribute = $afterAttributes[0]->newInstance();

    expect($classAttributes)->toHaveCount(1)
        ->and($pluginAttribute->target)->toBe(MessageRepository::class)
        ->and($afterAttributes)->toHaveCount(1)
        ->and($afterAttribute->sortOrder)->toBe(0);
});

// Helper functions

function createMentionTestMessage(
    string $body = '',
): Message {
    return new Message(
        id: 1,
        spaceId: 1,
        userId: 1,
        body: $body,
        bodyHtml: '<p>' . htmlspecialchars(string: $body) . '</p>',
        isPinned: false,
        editedAt: null,
        createdAt: new DateTimeImmutable('2026-02-24 00:00:00'),
    );
}

function createMentionMockDispatcher(): EventDispatcherInterface
{
    return new class () implements EventDispatcherInterface
    {
        public function dispatch(Event $event): void {}
    };
}

/**
 * @param array<Event> $dispatchedEvents
 */
function createMentionMockDispatcherWithHistory(
    array &$dispatchedEvents,
): EventDispatcherInterface {
    return new class ($dispatchedEvents) implements EventDispatcherInterface
    {
        /**
         * @param array<Event> $dispatchedEvents
         */
        public function __construct(
            /** @noinspection PhpPropertyOnlyWrittenInspection - Reference property modifies external variable */
            private array &$dispatchedEvents,
        ) {}

        public function dispatch(Event $event): void
        {
            $this->dispatchedEvents[] = $event;
        }
    };
}
