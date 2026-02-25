# Task 027: Unread Counts Per Space in Sidebar

**Status**: completed
**Depends on**: 008, 014
**Retry count**: 0

## Description
Calculate and display unread message counts per space in the sidebar. Uses space_memberships.last_read_message_id to count messages the user hasn't seen. Update last_read_message_id when user views a space.

## Context
- Unread count query: SELECT COUNT(*) FROM messages WHERE space_id = ? AND id > last_read_message_id
- When user views a space (SpaceController::show), update last_read_message_id to the latest message id
- Sidebar space items show unread count badge when > 0
- SpaceMembershipRepository.updateLastReadMessage() already created in task 008
- Count calculation can be a method on SpaceMembershipRepository or a service

## Requirements (Test Descriptions)
- [ ] `it calculates unread count for a user in a space`
- [ ] `it returns zero when user has read all messages`
- [ ] `it updates last_read_message_id when viewing a space`
- [ ] `it displays unread count badge next to space name in sidebar`
- [ ] `it hides badge when unread count is zero`

## Acceptance Criteria
- Unread count method on SpaceMembershipRepository or dedicated service
- SpaceController::show updates last_read_message_id
- Sidebar template shows unread badge (space-item-unread class)
- CSS for unread badge in space.css
- Count passed to template as part of space list data

## Implementation Notes
