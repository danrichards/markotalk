# Task 011: Message Entity, Migration, and Repository

**Status**: completed
**Depends on**: 007
**Retry count**: 0

## Description
Create the Message entity with all fields, its migration (with composite index on space_id, id), repository interface and implementation. The repository dispatches MessageCreatedEvent on save.

## Context
- Entity fields: id, space_id (FK spaces.id), user_id (FK users.id), body, body_html, is_pinned, edited_at, created_at
- Composite INDEX on (space_id, id) for efficient "messages since X" queries
- Repository needs: findBySpace (with limit), findBySpaceSince (for SSE polling), save (dispatches event), delete
- MessageRepository.save() dispatches MessageCreatedEvent for new messages — this is where plugins intercept
- Reference PostRepository pattern for event dispatching in save()

## Requirements (Test Descriptions)
- [ ] `it creates a Message entity with all required fields`
- [ ] `it has a migration with composite index on space_id and id`
- [ ] `it finds messages by space ordered by id ascending`
- [ ] `it finds messages by space since a given message id`
- [ ] `it saves a new message and dispatches MessageCreatedEvent`
- [ ] `it has module.php with MessageRepositoryInterface binding`

## Acceptance Criteria
- Message entity at `app/message/src/Entity/Message.php`
- MessageRepositoryInterface and MessageRepository in message module
- Migration at `database/migrations/20260224000004_create_messages.php`
- module.php at `app/message/module.php`
- save() method dispatches MessageCreatedEvent for new messages only

## Implementation Notes
