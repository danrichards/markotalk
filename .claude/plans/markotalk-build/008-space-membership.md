# Task 008: Space Membership Entity and Repository

**Status**: completed
**Depends on**: 002, 007
**Retry count**: 0

## Description
Create SpaceMembership entity (join table between users and spaces), its migration, and repository. Tracks which users have joined which spaces and their last read message position for unread counting.

## Context
- Fields: id, user_id (FK users.id), space_id (FK spaces.id), last_read_message_id (nullable FK messages.id), joined_at
- UNIQUE constraint on (user_id, space_id)
- Repository needs: findByUserAndSpace, findByUser (all spaces a user joined), join, leave, updateLastReadMessage
- Used by SpaceController for join/leave and by unread count calculations

## Requirements (Test Descriptions)
- [ ] `it creates a SpaceMembership entity with all required fields`
- [ ] `it has a migration with UNIQUE constraint on user_id and space_id`
- [ ] `it finds membership by user and space`
- [ ] `it finds all memberships for a user`
- [ ] `it creates a new membership when a user joins a space`
- [ ] `it deletes membership when a user leaves a space`
- [ ] `it updates last_read_message_id for a membership`

## Acceptance Criteria
- SpaceMembership entity at `app/space/src/Entity/SpaceMembership.php`
- SpaceMembershipRepositoryInterface and SpaceMembershipRepository in space module
- Migration at `database/migrations/20260224000003_create_space_memberships.php`
- Bindings added to space module.php

## Implementation Notes
