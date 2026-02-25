# Task 010: Space Templates (Sidebar, Chat View Shell)

**Status**: completed
**Depends on**: 005, 009
**Retry count**: 0

## Description
Create the space list sidebar template and the chat view template. The sidebar shows all spaces with active indicator. The chat view shows the space header, message feed area (empty for now — messages added in task 014), and message input form. Add space-specific CSS.

## Context
- Templates in `app/space/resources/views/`
- Sidebar content rendered in the layout's sidebar block
- Chat view: space name header, scrollable chat-feed div, chat-input form at bottom
- Space list shows: space name with # prefix, active state highlight, unread indicator placeholder
- Members list in sidebar below spaces
- CSS in `src/css/modules/space.css` — semantic classes only via @apply

## Requirements (Test Descriptions)
- [ ] `it renders the space list in the sidebar with all active spaces`
- [ ] `it highlights the currently selected space`
- [ ] `it renders the chat view with space name header`
- [ ] `it renders the message input form with textarea and send button`
- [ ] `it has space.css with all space-* classes defined via @apply`
- [ ] `it contains no Tailwind utility classes in any template`

## Acceptance Criteria
- Templates: space/index.latte (sidebar), space/show.latte (chat view)
- Sidebar: space-list, space-item, space-item-active classes
- Chat view: chat-area, chat-header, chat-feed, chat-input, chat-input-field, chat-input-send classes
- Input form POSTs to /spaces/{slug}/messages with CSRF token
- All styling in src/css/modules/space.css using @apply

## Implementation Notes
