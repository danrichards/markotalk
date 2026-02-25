# Task 013: Cursor-Paginated Message History

**Status**: completed
**Depends on**: 012
**Retry count**: 0

## Description
Implement cursor-based pagination for message history in MessageController::history and MessageRepository. Uses Marko's CursorPaginator and Cursor classes. Returns JSON with encoded cursor for "load more" on scroll-up.

## Context
- Cursor pagination uses message ID as cursor parameter (not offset)
- Query: SELECT * FROM messages WHERE space_id = ? AND id < ? ORDER BY id DESC LIMIT ?+1
- Fetch perPage+1 to detect hasMorePages
- Cursor encodes/decodes via Cursor::encode() and Cursor::decode()
- Response is CursorPaginator::toArray() which includes items, meta (per_page, has_more), links (previous, next cursors)
- Client JS calls GET /spaces/{slug}/messages?cursor={encoded} on scroll-up

## Requirements (Test Descriptions)
- [ ] `it returns the most recent messages when no cursor is provided`
- [ ] `it returns older messages when a cursor is provided`
- [ ] `it indicates has_more is true when more messages exist`
- [ ] `it indicates has_more is false when no more messages exist`
- [ ] `it returns messages in ascending order within each page`
- [ ] `it encodes the next cursor as base64 for the client`

## Acceptance Criteria
- MessageRepository has `findPaginated(spaceId, perPage, ?cursor)` method
- Returns CursorPaginator instance
- Default perPage is 50
- Messages within a page are ordered ascending (oldest first) for display
- Cursor contains `['id' => lastMessageId]`
- JSON response includes items, meta.has_more, links.next

## Implementation Notes
