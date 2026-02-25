# Task 018: Presence in SSE Heartbeat and Sidebar

**Status**: completed
**Depends on**: 010, 015, 017
**Retry count**: 0

## Description
Include online member data in SSE heartbeat frames and display the members list in the sidebar. The SSE heartbeat already fires at configured intervals — add presence data to it. Sidebar shows online/offline indicators.

## Context
- SSE heartbeat sends a 'presence' event with JSON array of online user data
- Sidebar members section below space list shows: username, online/offline indicator
- Green dot for online, gray dot for offline
- Members list: all members of the current space, sorted online-first
- CSS: member-list, member-item, is-online, is-offline classes in space.css

## Requirements (Test Descriptions)
- [ ] `it includes presence data in SSE heartbeat frames`
- [ ] `it sends a 'presence' event type with online user IDs`
- [ ] `it renders the members list in the sidebar`
- [ ] `it shows online indicator for users with recent activity`
- [ ] `it updates member status via JS when presence events arrive`

## Acceptance Criteria
- StreamController heartbeat includes SseEvent with event: 'presence', data: online user list
- Sidebar template includes member-list section
- JS listens for 'presence' events and updates DOM indicators
- CSS defines member-list, member-item, is-online, is-offline via @apply

## Implementation Notes
