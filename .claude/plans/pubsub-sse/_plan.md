# Plan: Replace SSE Polling with Pub/Sub Real-Time Streaming

## Created
2026-02-26

## Status
in_progress

## Objective
Replace the blocking `sleep(1)` poll loop in StreamController with PostgreSQL LISTEN/NOTIFY pub/sub via `marko/pubsub-pgsql`, so SSE clients receive events instantly without per-connection database polling.

## Scope

### In Scope
- Add `marko/pubsub`, `marko/pubsub-pgsql`, `marko/amphp` packages and configuration
- Rewrite StreamController to subscribe to a pub/sub channel instead of polling
- Publish message events (send, edit, delete) from MessageController
- Publish presence events from PresenceMiddleware
- Update client JS to handle the new single-channel event format
- Update existing tests to match new architecture

### Out of Scope
- Redis pub/sub driver (using PostgreSQL LISTEN/NOTIFY only)
- Changing the initial server-rendered page load (SpaceController stays as-is)
- Notification module changes
- Message history/pagination changes

## Architecture Decision: Single Channel Per Space

The `PgSqlSubscription` iterates listeners sequentially — subscribing to multiple channels blocks on the first listener indefinitely. Therefore, each SSE stream subscribes to **one channel: `space:{slug}`**. All event types (message, edit, delete, presence) are multiplexed on this single channel with a JSON payload containing a `type` field.

The `SseStream.iterateSubscription()` maps `Message.channel → SSE event type`, so the client receives events with type `space:{slug}` and routes by the `type` field in the parsed JSON payload.

PostgreSQL NOTIFY has an 8000-byte payload limit. MarkoTalk's max message length is 2000 chars — rendered HTML + JSON envelope stays well under 4KB even at maximum.

## Success Criteria
- [ ] New packages installed and configured for PostgreSQL pub/sub
- [ ] StreamController uses subscription-based SseStream (no polling, no sleep)
- [ ] MessageController publishes events after send/edit/delete
- [ ] PresenceMiddleware publishes presence events on space requests
- [ ] Client JS handles single-channel event routing
- [ ] All tests passing
- [ ] Code follows project standards

## Task Overview
| Task | Description | Depends On | Status |
|------|-------------|------------|--------|
| 001 | Add pub/sub packages and configuration | - | completed |
| 002 | Rewrite StreamController for pub/sub subscription | 001 | completed |
| 003 | Publish message events from MessageController | 001 | completed |
| 004 | Publish presence events from PresenceMiddleware | 001 | completed |
| 005 | Update client JS and template for single-channel events | 002, 003, 004 | completed |

## Architecture Notes

**Channel convention:** `space:{slug}` (prefix `marko:` added by driver → actual PG channel: `marko:space:general`)

**Event payload formats** (JSON strings published to channel `space:{slug}`):
| Type | Payload Fields |
|------|---------------|
| `message` | `type`, `id`, `userId`, `html` |
| `message_edited` | `type`, `id`, `bodyHtml` |
| `message_deleted` | `type`, `id` |
| `presence` | `type`, `onlineIds` |

**Key interfaces:**
- `PublisherInterface::publish(string $channel, Message $message): void`
- `SubscriberInterface::subscribe(string ...$channels): Subscription`
- `SseStream(subscription: $subscription, timeout: $timeout)` — subscription mode

**StreamController simplification:** Remove `MessageRepositoryInterface`, `UserRepositoryInterface`, `PresenceTrackerInterface`, `AuthManager`, `CsrfTokenManagerInterface`. Add `SubscriberInterface`. The controller just subscribes and forwards.

**Message HTML rendering:** Moves to MessageController. Published HTML is generic (no user-specific action buttons). Client JS adds edit/delete buttons for the current user's own messages using `data-current-user-id` from the template.

## Risks & Mitigations
- **Sequential listener iteration**: Mitigated by using single channel per space
- **Presence staleness**: Without polling, offline detection depends on other users making requests. Acceptable for an active chat app with 30s timeout.
- **PG NOTIFY payload limit (8KB)**: Max message is 2000 chars, rendered HTML + JSON well under 4KB
