# Task 033: Admin Space Controller

**Status**: completed
**Depends on**: 007, 032
**Retry count**: 0

## Description
Create AdminSpaceController for managing spaces: list all spaces (including archived), create new space, update name/description, and archive/unarchive. All routes protected by AdminMiddleware.

## Context
- Routes: GET /admin/spaces → index, POST /admin/spaces → create, PUT /admin/spaces/{id} → update, POST /admin/spaces/{id}/archive → archive
- Create: validate name (required, unique), generate slug from name, set created_by to current admin
- Update: validate name (unique except self), update description
- Archive: toggle is_archived boolean
- Uses #[Can('create', Space::class)] or #[Middleware(AdminMiddleware::class)]

## Requirements (Test Descriptions)
- [ ] `it lists all spaces including archived ones for admins`
- [ ] `it creates a new space with valid name and description`
- [ ] `it rejects creating a space with duplicate name`
- [ ] `it updates space name and description`
- [ ] `it archives a space`
- [ ] `it requires admin role for all actions`

## Acceptance Criteria
- AdminSpaceController at `app/admin/src/Controller/AdminSpaceController.php`
- All routes use AdminMiddleware
- Slug auto-generated from name
- Validation on create/update
- Archive toggles is_archived flag

## Implementation Notes
