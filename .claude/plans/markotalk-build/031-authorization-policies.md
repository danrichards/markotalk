# Task 031: Authorization Policies (Message, Space, User)

**Status**: completed
**Depends on**: 003, 008, 011
**Retry count**: 0

## Description
Create policy classes for Message, Space, and User authorization. Register them with the Gate. Policies enforce: message authors can edit/delete own messages, admins can do everything, only admins can manage spaces and users.

## Context
- MessagePolicy: edit(user, message) — own message or admin; delete(user, message) — own or admin; pin(user, message) — admin only
- SpacePolicy: create(user) — admin; update(user, space) — admin; archive(user, space) — admin
- UserPolicy: ban(user, targetUser) — admin; updateRole(user, targetUser) — admin
- Policies registered in module.php via Gate::policy() in boot callback
- Used in controllers via $gate->authorize() or #[Can] attribute
- AuthorizableInterface already implemented on User entity (task 002)

## Requirements (Test Descriptions)
- [x] `it allows message author to edit their own message`
- [x] `it allows admin to edit any message`
- [x] `it denies non-author non-admin from editing a message`
- [x] `it allows admin to create spaces`
- [x] `it denies regular users from creating spaces`
- [x] `it allows admin to ban users`
- [x] `it denies regular users from banning users`

## Acceptance Criteria
- MessagePolicy at `app/message/src/Policy/MessagePolicy.php`
- SpacePolicy at `app/space/src/Policy/SpacePolicy.php`
- UserPolicy at `app/user/src/Policy/UserPolicy.php`
- Each policy registered with Gate in respective module.php boot callback
- Policy methods receive nullable AuthorizableInterface user and return bool

## Implementation Notes
- Pure unit tests instantiate policies directly with User and entity objects
- MessagePolicy: edit/delete allow own message or admin; pin is admin-only
- SpacePolicy: create/update/archive are admin-only
- UserPolicy: ban/updateRole are admin-only
- Admin check: `$user->role === UserRole::Admin`
- Helper functions in test files use unique prefixes to avoid global function name collisions (pre-existing issue in codebase with makeGetRequest() and makeUser() across different test files)
