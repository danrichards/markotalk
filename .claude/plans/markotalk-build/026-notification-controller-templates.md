# Task 026: Notification Controller and Templates

**Status**: completed
**Depends on**: 005, 022
**Retry count**: 0

## Description
Create NotificationController with routes for listing notifications, marking individual as read, and marking all as read. Create notification list template and the bell icon with unread count in the header.

## Context
- Routes: GET /notifications → index, POST /notifications/{id}/read → markRead, POST /notifications/read → markAllRead
- Uses DatabaseNotificationRepository from marko/notification-database
- Bell icon in header shows unread count badge — included in base layout
- Notification list shows: type icon, message preview, timestamp, read/unread state
- Mark read updates read_at timestamp
- AuthMiddleware on all routes
- CSS: notification.css with notification-*, notification-badge classes

## Requirements (Test Descriptions)
- [ ] `it lists all notifications for the authenticated user`
- [ ] `it marks a single notification as read`
- [ ] `it marks all notifications as read`
- [ ] `it shows unread count in the header bell icon`
- [ ] `it has notification.css with semantic @apply styles`
- [ ] `it contains no Tailwind utility classes in templates`

## Acceptance Criteria
- NotificationController at `app/notification/src/Controller/NotificationController.php`
- Templates: notification/index.latte, header partial with bell icon
- Bell icon shows count badge when unread > 0
- CSS in src/css/modules/notification.css
- All routes require authentication

## Implementation Notes
