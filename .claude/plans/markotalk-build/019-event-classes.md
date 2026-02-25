# Task 019: Event Classes (MessageCreated, MentionDetected, UserRegistered)

**Status**: completed
**Depends on**: 001
**Retry count**: 0

## Description
Create all application event classes extending Marko's base Event class. These are dispatched by repositories and controllers and consumed by observers. Class-based events — NOT string-based.

## Context
- All events extend `Marko\Core\Event\Event` (abstract class with stopPropagation support)
- Events are dispatched via `EventDispatcherInterface::dispatch(Event $event)`
- Each event carries relevant data as constructor-injected readonly properties
- Events are the glue between modules — message module dispatches, notification module observes

## Requirements (Test Descriptions)
- [ ] `it creates MessageCreatedEvent with message property`
- [ ] `it creates MessageUpdatedEvent with message property`
- [ ] `it creates MessageDeletedEvent with message property`
- [ ] `it creates MentionDetectedEvent with message and username properties`
- [ ] `it creates UserRegisteredEvent with user property`
- [ ] `it allows stopping propagation on all event types`

## Acceptance Criteria
- MessageCreatedEvent at `app/message/src/Event/MessageCreatedEvent.php`
- MessageUpdatedEvent at `app/message/src/Event/MessageUpdatedEvent.php`
- MessageDeletedEvent at `app/message/src/Event/MessageDeletedEvent.php`
- MentionDetectedEvent at `app/message/src/Event/MentionDetectedEvent.php`
- UserRegisteredEvent at `app/user/src/Event/UserRegisteredEvent.php`
- All use readonly properties, constructor injection, declare strict_types

## Implementation Notes
