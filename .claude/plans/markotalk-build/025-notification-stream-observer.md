# Task 025: NotificationStreamObserver (SSE Push)

**Status**: completed
**Depends on**: 015, 022
**Retry count**: 0

## Description
Create NotificationStreamObserver that listens for MessageCreatedEvent and pushes notification count updates to connected SSE clients. This allows the notification badge to update in real-time without polling.

## Context
- Observer attribute: `#[Observer(event: MessageCreatedEvent::class)]`
- When a new message is created, users in that space who are NOT currently viewing it may have increased unread counts
- Rather than pushing directly to SSE (which is per-space), this observer can store a "notification pending" flag that the SSE heartbeat picks up
- Alternative: the SSE heartbeat already polls — just include notification count in heartbeat data
- Simpler approach: SSE heartbeat includes unread notification count for the connected user

## Requirements (Test Descriptions)
- [ ] `it listens for MessageCreatedEvent`
- [ ] `it uses #[Observer(event: MessageCreatedEvent::class)]`
- [ ] `it identifies users who should receive notification count updates`
- [ ] `it stores notification state for SSE heartbeat pickup`

## Acceptance Criteria
- NotificationStreamObserver at `app/notification/src/Observer/NotificationStreamObserver.php`
- Uses class-based event observer pattern
- Integrates with SSE heartbeat to deliver notification counts
- Does not duplicate notifications — works with the existing notification system

## Implementation Notes
