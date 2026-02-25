# Task 006: Auth Templates (Login, Register)

**Status**: completed
**Depends on**: 004, 005
**Retry count**: 0

## Description
Create login and register Latte templates with semantic CSS classes. Add auth-specific styles to src/css/modules/auth.css using @apply. Templates extend the base layout and display forms with validation error handling.

## Context
- Templates at `app/user/resources/views/auth/login.latte` and `register.latte`
- Rendered as `user::auth/login` and `user::auth/register` from AuthController
- Must use semantic class names only (auth-form, auth-field, auth-label, auth-error, etc.)
- Forms POST to /login and /register with CSRF token
- Show validation errors per field
- Link between login and register pages

## Requirements (Test Descriptions)
- [ ] `it renders login form with email and password fields`
- [ ] `it renders register form with username, email, password, and password confirmation fields`
- [ ] `it displays validation errors next to form fields`
- [ ] `it includes CSRF token in both forms`
- [ ] `it has auth.css with semantic @apply styles for all auth classes`
- [ ] `it contains no Tailwind utility classes in template markup`

## Acceptance Criteria
- login.latte and register.latte extend base layout
- Forms use semantic class names (auth-form, auth-field, auth-field-error, etc.)
- src/css/modules/auth.css defines all auth-* classes via @apply
- CSRF token included as hidden field `_token`
- Links: login page links to register, register page links to login

## Implementation Notes
