# Task 034: Admin User Controller

**Status**: completed
**Depends on**: 003, 032
**Retry count**: 0

## Description
Create AdminUserController for managing users: list all users, ban/unban, and update roles. All routes protected by AdminMiddleware.

## Context
- Routes: GET /admin/users → index, POST /admin/users/{id}/ban → ban, POST /admin/users/{id}/unban → unban, POST /admin/users/{id}/role → updateRole
- Ban sets is_banned to true, unban sets to false
- UpdateRole changes user.role to the submitted value (UserRole enum)
- Cannot ban/change role of yourself (prevents admin lockout)
- Uses authorization policies from task 031

## Requirements (Test Descriptions)
- [ ] `it lists all users for admins`
- [ ] `it bans a user`
- [ ] `it unbans a user`
- [ ] `it updates a user role`
- [ ] `it prevents admin from banning themselves`
- [ ] `it requires admin role for all actions`

## Acceptance Criteria
- AdminUserController at `app/admin/src/Controller/AdminUserController.php`
- All routes use AdminMiddleware
- Self-action prevention on ban and role change
- Redirects back to /admin/users after each action

## Implementation Notes
