# Task 039: Notification Migration

**Status**: completed
**Depends on**: 001
**Retry count**: 0

## Description
Create the migration for the notifications table used by marko/notification-database. The schema follows the standard format expected by DatabaseChannel and DatabaseNotificationRepository.

## Context
- Table schema from DatabaseChannel: id (UUID varchar 36), type (varchar 255), notifiable_type (varchar 255), notifiable_id (varchar 255), data (text, JSON), read_at (timestamp nullable), created_at (timestamp)
- Index on (notifiable_type, notifiable_id) for efficient per-user queries
- Index on read_at for unread filtering
- This is a standard schema expected by the framework's notification-database package

## Requirements (Test Descriptions)
- [ ] `it has a migration that creates the notifications table`
- [ ] `it has id as varchar 36 primary key for UUID`
- [ ] `it has indexes on notifiable_type and notifiable_id`
- [ ] `it has nullable read_at timestamp`

## Acceptance Criteria
- Migration at `database/migrations/20260224000006_create_notifications.php`
- Schema matches marko/notification-database expectations exactly
- Indexes for efficient querying

## Implementation Notes
