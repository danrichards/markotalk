# Task 022: Notification Types (MentionNotification, WelcomeNotification)

**Status**: completed
**Depends on**: 019
**Retry count**: 0

## Description
Create MentionNotification and WelcomeNotification classes implementing Marko's NotificationInterface. These define what channels to use and how to format the notification data for database storage.

## Context
- NotificationInterface requires: channels(), toMail(), toDatabase()
- Both notifications use 'database' channel only (no email in v1)
- MentionNotification: carries message and mentioning user info, toDatabase returns {message_id, space_slug, author_username, message_preview}
- WelcomeNotification: carries user info, toDatabase returns {message: "Welcome to MarkoTalk!"}
- toMail can throw or return empty — we use mail-log driver anyway

## Requirements (Test Descriptions)
- [ ] `it creates MentionNotification with database channel`
- [ ] `it formats MentionNotification for database with message details`
- [ ] `it creates WelcomeNotification with database channel`
- [ ] `it formats WelcomeNotification for database with welcome message`
- [ ] `it carries the relevant context (message, user) for each notification type`

## Acceptance Criteria
- MentionNotification at `app/notification/src/Notification/MentionNotification.php`
- WelcomeNotification at `app/notification/src/Notification/WelcomeNotification.php`
- Both implement NotificationInterface
- channels() returns ['database']
- toDatabase() returns array with all needed display data
- notification module.php created with any needed bindings

## Implementation Notes
