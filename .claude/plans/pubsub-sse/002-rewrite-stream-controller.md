# Task 002: Rewrite StreamController for pub/sub subscription

**Status**: complete
**Depends on**: 001
**Retry count**: 0

## Description
Replace the polling-based StreamController with a subscription-based approach. The controller subscribes to `space:{slug}` via `SubscriberInterface` and creates an `SseStream` with the subscription instead of a `dataProvider` closure. This eliminates the `sleep(1)` poll loop and all per-poll database queries.

## Context
- Related files:
  - `app/message/src/Controller/StreamController.php` — rewrite this
  - `app/message/tests/Unit/StreamControllerTest.php` — rewrite tests
  - `app/message/tests/Unit/PresenceSseTest.php` — rewrite tests
  - `~/Sites/marko/packages/sse/src/SseStream.php` — subscription mode: `new SseStream(subscription: $sub, timeout: $timeout)`
  - `~/Sites/marko/packages/pubsub/src/SubscriberInterface.php` — `subscribe(string ...$channels): Subscription`
- Patterns to follow: Constructor injection, named parameters, `#[Get]` route attribute
- The `SseStream` subscription mode maps `Message.channel → SSE event type` and `Message.payload → SSE data`
- The existing `renderMessageHtml()` method is removed — HTML rendering moves to MessageController (task 003)

**New constructor signature:**
```php
public function __construct(
    private SpaceRepositoryInterface $spaces,
    private SubscriberInterface $subscriber,
    private ConfigRepositoryInterface $config,
) {}
```

**New stream method flow:**
1. Validate space exists via `$this->spaces->findBySlug(slug: $slug)`
2. Get timeout from config: `$this->config->getInt(key: 'markotalk.sse_timeout')`
3. Subscribe: `$subscription = $this->subscriber->subscribe("space:{$slug}")`
4. Return: `new StreamingResponse(stream: new SseStream(subscription: $subscription, timeout: $timeout))`

## Requirements (Test Descriptions)

- [x] `it returns a StreamingResponse with text/event-stream content type`
- [x] `it creates an SseStream with a subscription instead of a dataProvider`
- [x] `it subscribes to the space:{slug} channel`
- [x] `it uses the configured sse_timeout for the stream`
- [x] `it requires AuthMiddleware and PresenceMiddleware`
- [x] `it does not inject MessageRepositoryInterface, UserRepositoryInterface, or AuthManager`

## Acceptance Criteria
- All requirements have passing tests
- StreamController no longer has any polling logic (no `sleep`, no `dataProvider`, no DB queries for messages/presence)
- Code follows code standards (strict types, named params, constructor injection)
- Route attribute preserved: `#[Get('/spaces/{slug}/stream', middleware: [AuthMiddleware::class, PresenceMiddleware::class])]`

## Implementation Notes
- Replaced polling `dataProvider` closure with `SubscriberInterface::subscribe()` returning a `Subscription`
- New constructor injects only `SpaceRepositoryInterface`, `SubscriberInterface`, and `ConfigRepositoryInterface`
- `renderMessageHtml()` private method removed — HTML rendering moves to MessageController (task 003)
- `SseStream` now uses subscription mode: `new SseStream(subscription: $subscription, timeout: $timeout)`
- Route attribute preserved with both `AuthMiddleware` and `PresenceMiddleware`
- `stream()` method no longer takes a `Request` parameter since `Last-Event-ID` was only needed for polling
- Rewrote `StreamControllerTest.php` with new pub/sub mocks (`SubscriberInterface`, `Subscription`, `ConfigRepositoryInterface`)
- Rewrote `PresenceSseTest.php` with new pub/sub mocks; template tests updated to match actual Latte template (`$onlineUserIds` instead of `$member->isOnline`)
