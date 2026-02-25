# Task 038: Message Pin Controller Action

**Status**: completed
**Depends on**: 012, 031
**Retry count**: 0

## Description
Add pin/unpin action to MessageController. Only admins can pin/unpin messages. Pinned messages display with a visual indicator in the chat feed.

## Context
- Route: POST /messages/{id}/pin → MessageController::pin
- Toggles is_pinned boolean on the message
- Uses MessagePolicy::pin() — admin only
- Pinned messages show with message-pinned class (already defined in task 014 CSS)
- Pin action authorized via $gate->authorize('pin', $message)

## Requirements (Test Descriptions)
- [ ] `it pins a message when admin toggles pin`
- [ ] `it unpins a pinned message when admin toggles pin`
- [ ] `it rejects pin action from non-admin users`
- [ ] `it returns 403 when unauthorized`
- [ ] `it redirects back to the space after pin/unpin`

## Acceptance Criteria
- Pin action added to MessageController
- Route: POST /messages/{id}/pin
- Uses Gate authorization
- Toggles is_pinned and saves via repository
- Message partial already shows pinned indicator (from task 014)

## Implementation Notes
