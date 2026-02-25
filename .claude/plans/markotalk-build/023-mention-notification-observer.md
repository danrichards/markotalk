# Task 023: MentionNotificationObserver

**Status**: completed
**Depends on**: 021, 022
**Retry count**: 0

## Description
Create MentionNotificationObserver that listens for MentionDetectedEvent and sends a MentionNotification to the mentioned user via NotificationSender. Resolves username to user, skips if user doesn't exist or is the message author (no self-mention notifications).

## Context
- Observer attribute: `#[Observer(event: MentionDetectedEvent::class)]`
- CRITICAL: observer uses class-based event reference, NOT string
- Observer has `handle(MentionDetectedEvent $event): void` method
- Looks up username via UserRepository — skip if not found
- Skip if mentioned user is the message author (no self-notifications)
- Uses NotificationSender::send() with MentionNotification
- Decoupled: message module dispatches event, notification module observes

## Requirements (Test Descriptions)
- [ ] `it sends a MentionNotification when a valid user is mentioned`
- [ ] `it skips notification when mentioned username does not exist`
- [ ] `it skips notification when user mentions themselves`
- [ ] `it uses #[Observer(event: MentionDetectedEvent::class)]`
- [ ] `it resolves the mentioned username to a User entity`

## Acceptance Criteria
- MentionNotificationObserver at `app/message/src/Observer/MentionNotificationObserver.php`
- Uses `#[Observer(event: MentionDetectedEvent::class)]` attribute
- Constructor injects UserRepositoryInterface and NotificationSender
- handle() method follows the skip logic correctly
- No coupling to SSE or UI — just creates the notification

## Implementation Notes
