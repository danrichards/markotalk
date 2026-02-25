# Task 035: Admin Templates

**Status**: completed
**Depends on**: 005, 033, 034
**Retry count**: 0

## Description
Create admin panel templates for space management and user management. Simple table-based UI with action buttons. Add admin-specific CSS.

## Context
- Templates in `app/admin/resources/views/`
- Space management: table with name, slug, status (active/archived), actions (edit, archive)
- User management: table with username, email, role, ban status, actions
- Create space form: name, description inputs
- Edit space form: name, description inputs (inline or separate page)
- CSS in `src/css/modules/admin.css` — semantic classes only

## Requirements (Test Descriptions)
- [ ] `it renders the space management table with all spaces`
- [ ] `it renders the create space form`
- [ ] `it renders the user management table with all users`
- [ ] `it shows ban/unban and role change actions per user`
- [ ] `it has admin.css with all admin-* classes defined via @apply`
- [ ] `it contains no Tailwind utility classes in any template`

## Acceptance Criteria
- Templates: admin/spaces/index.latte, admin/users/index.latte
- Tables: admin-table, admin-table-header, admin-table-row classes
- Actions: admin-action, admin-action-danger classes for buttons
- Forms use semantic classes (admin-form, admin-field)
- All styling in src/css/modules/admin.css

## Implementation Notes
