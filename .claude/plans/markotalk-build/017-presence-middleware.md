# Task 017: Presence Tracking Middleware

**Status**: completed
**Depends on**: 003
**Retry count**: 0

## Description
Create middleware that updates the authenticated user's last_seen_at timestamp on each request. Users with last_seen_at within the configured presence_timeout are considered online. Also create a PresenceTracker service interface and DatabasePresenceTracker implementation (swappable via Preference).

## Context
- Middleware runs on every authenticated request
- Updates users.last_seen_at to current datetime
- PresenceTrackerInterface: updateLastSeen(user), isOnline(user), getOnlineUsers()
- DatabasePresenceTracker queries users WHERE last_seen_at > NOW() - presence_timeout
- presence_timeout from config/markotalk.php (default 300 seconds)
- This is a Preference showcase — could be swapped for RedisPresenceTracker

## Requirements (Test Descriptions)
- [ ] `it updates last_seen_at for authenticated users on each request`
- [ ] `it skips presence update for unauthenticated requests`
- [ ] `it considers a user online when last_seen_at is within timeout`
- [ ] `it considers a user offline when last_seen_at exceeds timeout`
- [ ] `it returns all online users for a given space`

## Acceptance Criteria
- PresenceMiddleware at `app/user/src/Middleware/PresenceMiddleware.php`
- PresenceTrackerInterface at `app/user/src/Service/PresenceTrackerInterface.php`
- DatabasePresenceTracker at `app/user/src/Service/DatabasePresenceTracker.php`
- Bindings in user module.php
- Middleware registered globally or applied to authenticated routes

## Implementation Notes
