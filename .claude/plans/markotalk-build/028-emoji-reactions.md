# Task 028: Emoji Reactions (Entity, Controller, UI)

**Status**: completed
**Depends on**: 012, 014
**Retry count**: 0

## Description
Add emoji reactions to messages. Users can react with emoji, toggle reactions off, and see reaction counts per emoji on each message. Lightweight inline emoji picker in the UI.

## Context
- Routes: POST /messages/{id}/reactions → react, DELETE /messages/{id}/reactions/{emoji} → unreact
- React: create Reaction with message_id, user_id, emoji — UNIQUE(message_id, user_id, emoji)
- Unreact: delete the matching Reaction
- Toggle behavior: if reaction exists, remove it; if not, add it
- Display: under each message, show emoji + count buttons (e.g., "👍 3 ❤️ 1")
- Emoji picker: lightweight inline dropdown with common emoji
- Uses Reaction entity and ReactionRepository from task 030

## Requirements (Test Descriptions)
- [ ] `it adds a reaction to a message`
- [ ] `it removes a reaction from a message`
- [ ] `it prevents duplicate reactions (same user, same emoji, same message)`
- [ ] `it displays reaction counts under messages`
- [ ] `it has an inline emoji picker UI`
- [ ] `it updates reactions via fetch without page reload`

## Acceptance Criteria
- React/unreact actions in MessageController
- Reaction display in _message.latte partial
- Emoji picker in JS (simple dropdown with common emoji)
- CSS: message-reactions, message-reaction, message-reaction-active classes
- Toggle behavior: clicking own reaction removes it

## Implementation Notes
