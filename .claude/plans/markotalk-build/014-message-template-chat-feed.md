# Task 014: Message Template Partial and Chat Feed

**Status**: completed
**Depends on**: 010, 012
**Retry count**: 0

## Description
Create the message partial template (_message.latte) reused in both page render and SSE streaming. Integrate into the chat feed in space/show.latte. Add message-specific CSS. Display author, timestamp, body_html, pinned indicator, and action buttons.

## Context
- Partial at `app/message/resources/views/_message.latte`
- Reused in: space/show.latte (initial render loop) and SSE streaming (rendered as HTML fragment)
- Shows: avatar placeholder, author display_name, timestamp, body_html (noescape), pinned badge, edit/delete buttons (if owned)
- Chat feed scrolls to bottom on initial load
- CSS in `src/css/modules/message.css` — semantic classes only
- Class names: message, message-header, message-author, message-body, message-timestamp, message-pinned, message-actions

## Requirements (Test Descriptions)
- [ ] `it renders a message with author name and body`
- [ ] `it renders body_html with noescape filter`
- [ ] `it shows a pinned badge for pinned messages`
- [ ] `it shows edit and delete actions for the message author`
- [ ] `it has message.css with all message-* classes defined via @apply`
- [ ] `it contains no Tailwind utility classes in the template`

## Acceptance Criteria
- _message.latte partial renders a single message with all visual elements
- space/show.latte includes the partial in a foreach loop
- Chat feed div has id="chat-feed" for JS targeting
- Message div has data-message-id attribute for JS identification
- All styling in src/css/modules/message.css

## Implementation Notes
