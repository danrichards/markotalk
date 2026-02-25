# Task 015: SSE Stream Controller

**Status**: completed
**Depends on**: 011
**Retry count**: 0

## Description
Create StreamController that serves an SSE endpoint for real-time message delivery. Uses SseStream from marko/sse with a data provider closure that polls for new messages since the last event ID.

## Context
- Route: GET /spaces/{slug}/stream → StreamController::stream
- Uses SseStream with dataProvider closure, pollInterval, heartbeatInterval, timeout from config
- Data provider: query messages WHERE space_id = ? AND id > lastEventId, return as SseEvent array
- Each SseEvent: data is rendered message HTML, event is 'message', id is message.id
- StreamingResponse wraps SseStream with text/event-stream headers
- Client sends Last-Event-ID header for reconnection — extract from request
- AuthMiddleware required

## Requirements (Test Descriptions)
- [ ] `it returns a StreamingResponse with text/event-stream content type`
- [ ] `it uses SseStream with config-driven poll interval and timeout`
- [ ] `it queries messages newer than the Last-Event-ID`
- [ ] `it formats new messages as SseEvent with message HTML as data`
- [ ] `it sends heartbeat pings at the configured interval`
- [ ] `it requires authentication`

## Acceptance Criteria
- StreamController at `app/message/src/Controller/StreamController.php`
- Uses `new StreamingResponse(new SseStream(...))` pattern
- Data provider reads config for poll_interval, heartbeat_interval, timeout
- Extracts Last-Event-ID from request header, defaults to 0
- Each event has: data (message HTML), event ('message'), id (message id)
- Route: #[Get('/spaces/{slug}/stream')]

## Implementation Notes
