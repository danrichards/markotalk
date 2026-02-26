# Task 005: Update client JS and template for single-channel events

**Status**: completed
**Depends on**: 002, 003, 004
**Retry count**: 0

## Description
Update the client-side JavaScript and Latte template to handle the new single-channel event format. Instead of three separate event listeners (`message`, `message_edited`, `presence`), listen for a single `space:{slug}` event and route by the `type` field in the JSON payload. Add client-side rendering of action buttons (edit/delete) for the current user's own messages, and add a `message_deleted` handler.

## Context
- Related files:
  - `public/js/app.js` — modify this
  - `app/space/resources/views/space/show.latte` — add `data-current-user-id` attribute
  - `tests/Unit/AppJsTest.php` — update tests
  - `app/message/tests/Unit/PresenceSseTest.php` — update JS-related tests
- Patterns to follow: Existing vanilla JS patterns (IIFE, `dataset`, `querySelector`, `insertAdjacentHTML`)
- The `spaceSlug` is already available via `chatFeed.dataset.spaceSlug`
- The CSRF token is already available via `document.querySelector('[name="_token"]').value`
- SSE event type will be `space:{slug}` (the pub/sub channel name after prefix stripping)

**Key changes to app.js:**
1. Replace `eventSource.addEventListener('message', ...)` with `eventSource.addEventListener('space:' + spaceSlug, ...)`
2. Parse `JSON.parse(e.data)` and switch on `event.type`
3. For `type: "message"`: append `event.html`, check `event.userId === currentUserId` and inject action buttons
4. For `type: "message_edited"`: update body HTML (same as before)
5. For `type: "message_deleted"`: remove the message element from DOM
6. For `type: "presence"`: update member indicators (same as before)

**Template change:**
Add `data-current-user-id="{$currentUser->id}"` to the `#chat-feed` div so JS can access the current user's ID.

**Action button injection:**
When a new message arrives and `event.userId` matches the current user, append edit/delete button HTML after the message body. Use the same HTML structure as `_message.latte` action buttons.

## Requirements (Test Descriptions)

- [ ] `it listens for space:{slug} events instead of separate message, message_edited, and presence events`
- [ ] `it parses the event data as JSON and routes by type field`
- [ ] `it appends message HTML for type message events`
- [ ] `it adds action buttons for the current user's own messages`
- [ ] `it removes message elements for type message_deleted events`
- [ ] `it updates member indicators for type presence events`
- [ ] `it has a data-current-user-id attribute on the chat feed element`

## Acceptance Criteria
- All requirements have passing tests
- Existing JS functionality preserved (auto-scroll, deduplication, form submission, reactions, inline editing, scroll-to-top pagination)
- Template renders `data-current-user-id` with the authenticated user's ID
- Action buttons match existing HTML structure (same CSS classes, data attributes)
- Code follows project standards (vanilla JS, no framework)

## Implementation Notes
(Left blank - filled in by programmer during implementation)
