# Task 003: Publish message events from MessageController

**Status**: complete
**Depends on**: 001
**Retry count**: 0

## Description
Add event publishing to `MessageController` so that when messages are sent, edited, or deleted, events are published to the `space:{slug}` pub/sub channel. The published payload includes the event type and data. For new messages, generic HTML (without user-specific action buttons) is published so all subscribers receive the same content.

## Context
- Related files:
  - `app/message/src/Controller/MessageController.php` — modify this
  - `app/message/tests/Unit/MessageControllerTest.php` — add new tests
  - `~/Sites/marko/packages/pubsub/src/PublisherInterface.php` — `publish(string $channel, Message $message): void`
  - `~/Sites/marko/packages/pubsub/src/Message.php` — `new Message(channel: $channel, payload: $jsonString)`
  - `app/message/resources/views/_message.latte` — reference for HTML structure
- Patterns to follow: Existing MessageController patterns (constructor injection, named params)
- The controller needs `UserRepositoryInterface` to look up author display names for message HTML rendering
- The space slug is needed for the channel name — the `send()` method already receives `$slug` from the route
- For `edit()` and `delete()`, the space slug must be derived from the message's `spaceId` via `SpaceRepositoryInterface`

**Payload formats:**
- Send: `{"type":"message","id":123,"userId":1,"html":"<div class='message' ...>...</div>"}`
- Edit: `{"type":"message_edited","id":123,"bodyHtml":"updated html"}`
- Delete: `{"type":"message_deleted","id":123}`

**Message HTML rendering:**
Move rendering from `StreamController::renderMessageHtml()` to a private method on `MessageController`. Render generic HTML WITHOUT action buttons (edit/delete) — the client adds these based on `data-user-id` and current user ID. Include: avatar div, author name, timestamp, optional pin badge, message body. The rendered HTML should match the `_message.latte` template structure.

**New dependencies to inject:**
- `PublisherInterface $publisher`
- `UserRepositoryInterface $users` (for author name lookup)

## Requirements (Test Descriptions)

- [x] `it publishes a message event to space:{slug} channel after sending a message`
- [x] `it includes message id, userId, and rendered HTML in the published message payload`
- [x] `it publishes a message_edited event after editing a message`
- [x] `it includes message id and updated bodyHtml in the edited message payload`
- [x] `it publishes a message_deleted event after deleting a message`
- [x] `it includes message id in the deleted message payload`
- [x] `it renders message HTML without user-specific action buttons`

## Acceptance Criteria
- All requirements have passing tests
- Existing MessageController tests still pass (send, edit, delete, validation, auth)
- Published HTML matches `_message.latte` structure (same CSS classes, data attributes)
- No user-specific content in published HTML (no CSRF tokens, no edit/delete buttons)
- Code follows code standards

## Implementation Notes
- Added `PublisherInterface $publisher` and `UserRepositoryInterface $users` as optional nullable constructor parameters to `MessageController`
- Made `SpaceMembershipRepositoryInterface $memberships` optional (nullable) to align with test factory patterns
- The `send()` method publishes to `space:{slug}` channel with type `message` payload including rendered HTML
- The `edit()` method looks up space via `SpaceRepositoryInterface::find(id: $message->spaceId)` to get the slug, publishes `message_edited` payload
- The `delete()` method similarly derives slug from spaceId, publishes `message_deleted` payload
- Added private `renderMessageHtml()` method that generates HTML without user-specific action buttons (no CSRF tokens, no edit/delete buttons)
- Fixed `makeMessageRepository` test stub in `MessageControllerTest.php` to implement missing `findEditedSince()` method
- Fixed same missing `findEditedSince()` in `EmojiReactionTest.php`, `MessagePinTest.php`, and `RateLimitingTest.php`
- Added helper factories `makePublisher()` and `makeUserRepository()` to the test file
