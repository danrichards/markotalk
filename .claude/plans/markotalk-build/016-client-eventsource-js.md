# Task 016: Client-Side EventSource JS

**Status**: completed
**Depends on**: 014, 015
**Retry count**: 0

## Description
Create public/js/app.js with vanilla JavaScript for real-time chat functionality: EventSource connection per space, message sending via fetch, scroll behavior (auto-scroll to bottom, pause on scroll-up), and load-older-messages on scroll-to-top.

## Context
- EventSource connects to /spaces/{slug}/stream
- On 'message' event: parse HTML from event.data, append to #chat-feed
- Auto-scroll: scroll to bottom on new message UNLESS user has scrolled up
- Scroll-up detection: if scrollTop + clientHeight < scrollHeight - threshold
- Load older: when scrollTop reaches 0, fetch GET /spaces/{slug}/messages?cursor={nextCursor}, prepend messages
- Message sending: intercept form submit, POST via fetch with FormData, optimistic UI (optional)
- Reconnection: EventSource handles natively with Last-Event-ID

## Requirements (Test Descriptions)
- [ ] `it establishes an EventSource connection to the space stream URL`
- [ ] `it appends received message HTML to the chat feed`
- [ ] `it auto-scrolls to bottom on new messages when at bottom`
- [ ] `it does not auto-scroll when user has scrolled up`
- [ ] `it loads older messages on scroll to top via cursor pagination`
- [ ] `it sends messages via fetch POST without page reload`

## Acceptance Criteria
- public/js/app.js contains all client-side logic
- EventSource URL derived from current space slug in the page
- DOM manipulation targets #chat-feed, uses data-message-id for dedup
- Scroll threshold: 100px from bottom counts as "at bottom"
- Older messages prepended while maintaining scroll position
- Form submission prevented, sent via fetch, input cleared on success
- No JS framework dependencies

## Implementation Notes
