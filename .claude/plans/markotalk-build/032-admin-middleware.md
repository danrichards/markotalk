# Task 032: Admin Middleware

**Status**: completed
**Depends on**: 003
**Retry count**: 0

## Description
Create AdminMiddleware that checks if the authenticated user has the admin role. Returns 403 if not admin. Applied to all admin routes.

## Context
- Checks user.role === UserRole::Admin
- Returns 403 Forbidden response for non-admin users
- Returns 401 Unauthorized for unauthenticated users
- Applied via #[Middleware(AdminMiddleware::class)] on admin controllers
- Depends on authentication being resolved first

## Requirements (Test Descriptions)
- [ ] `it allows admin users to proceed`
- [ ] `it returns 403 for non-admin authenticated users`
- [ ] `it returns 401 for unauthenticated users`
- [ ] `it implements MiddlewareInterface`

## Acceptance Criteria
- AdminMiddleware at `app/admin/src/Middleware/AdminMiddleware.php`
- Implements Marko\Routing\Middleware\MiddlewareInterface
- Checks UserRole enum on authenticated user
- admin module has composer.json and module.php

## Implementation Notes
