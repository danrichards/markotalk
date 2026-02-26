# Task 004: Publish presence events from PresenceMiddleware

**Status**: completed
**Depends on**: 001
**Retry count**: 0

## Description
Enhance `PresenceMiddleware` to publish presence events to the relevant space channel after updating `lastSeenAt`. When a request matches a space URL pattern (`/spaces/{slug}` or `/spaces/{slug}/*`), the middleware computes the current online user IDs and publishes a presence event to `space:{slug}`.

## Context
- Related files:
  - `app/user/src/Middleware/PresenceMiddleware.php` — modify this
  - `app/user/tests/Unit/PresenceTest.php` — add new tests
  - `~/Sites/marko/packages/pubsub/src/PublisherInterface.php` — `publish(string $channel, Message $message): void`
  - `~/Sites/marko/packages/pubsub/src/Message.php` — `new Message(channel: $channel, payload: $jsonString)`
  - `app/user/src/Service/PresenceTrackerInterface.php` — `getOnlineUsers(): User[]`
- Patterns to follow: Existing PresenceMiddleware pattern (constructor injection, guard check)
- The middleware already has `GuardInterface` and `PresenceTrackerInterface` injected
- Add `PublisherInterface` as a new dependency

**URL detection logic:**
Extract space slug from the request URI using a regex like `/^\/spaces\/([a-z0-9-]+)/`. If matched, publish presence to `space:{slug}`.

**Payload format:**
```json
{"type":"presence","onlineIds":[1,2,3]}
```

**Important:** The `PublisherInterface` should be nullable (`?PublisherInterface`) to maintain backward compatibility — the middleware should still work without pub/sub configured (e.g., in tests or non-pub/sub environments).

## Requirements (Test Descriptions)

- [x] `it publishes a presence event to the space channel on space requests`
- [x] `it includes online user IDs in the presence event payload`
- [x] `it extracts the space slug from the request URI`
- [x] `it does not publish presence events on non-space requests`
- [x] `it still updates lastSeenAt regardless of whether presence is published`
- [x] `it works without a publisher configured (nullable dependency)`

## Acceptance Criteria
- All requirements have passing tests
- Existing PresenceTest tests still pass (updateLastSeen, skip unauthenticated)
- Middleware continues to call `tracker->updateLastSeen()` for all authenticated requests
- Presence publishing only occurs on space URL requests
- Code follows code standards

## Implementation Notes
- Added `?PublisherInterface $publisher = null` as nullable dependency to `PresenceMiddleware`
- Added private `publishPresenceIfSpaceRequest()` method using regex `/^\/spaces\/([a-z0-9-]+)/`
- Publishes JSON `{"type":"presence","onlineIds":[...]}` to `space:{slug}` channel
- Gets online IDs via `tracker->getOnlineUsers()` and maps to `->id`
- Existing `updateLastSeen()` behavior unchanged
- 6 new tests added covering all requirements; existing tests still pass
