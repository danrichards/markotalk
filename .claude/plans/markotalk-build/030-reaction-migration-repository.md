# Task 030: Reaction Migration and Repository

**Status**: completed
**Depends on**: 011
**Retry count**: 0

## Description
Create the Reaction entity, its migration, and ReactionRepository. Reactions are emoji responses on messages with a UNIQUE constraint preventing duplicate reactions.

## Context
- Entity fields: id, message_id (FK messages.id), user_id (FK users.id), emoji (varchar 50)
- UNIQUE constraint on (message_id, user_id, emoji)
- Repository needs: findByMessage, findByMessageGrouped (counts per emoji), addReaction, removeReaction, findByMessageAndUser
- Migration creates reactions table

## Requirements (Test Descriptions)
- [ ] `it creates a Reaction entity with all required fields`
- [ ] `it has a migration with UNIQUE constraint on message_id, user_id, and emoji`
- [ ] `it finds all reactions for a message`
- [ ] `it groups reactions by emoji with counts`
- [ ] `it adds a new reaction`
- [ ] `it removes an existing reaction`

## Acceptance Criteria
- Reaction entity at `app/message/src/Entity/Reaction.php`
- ReactionRepositoryInterface and ReactionRepository in message module
- Migration at `database/migrations/20260224000005_create_reactions.php`
- Bindings added to message module.php
- Grouped query returns [{emoji: '👍', count: 3, user_reacted: true}, ...]

## Implementation Notes
