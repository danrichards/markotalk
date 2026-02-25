# Task 004: Auth Controller (Register, Login, Logout)

**Status**: completed
**Depends on**: 003
**Retry count**: 0

## Description
Create AuthController with registration, login, and logout endpoints. Uses SessionGuard for authentication, Validator for input validation, HasherInterface for password hashing, and EventDispatcherInterface to dispatch UserRegisteredEvent.

## Context
- Route attributes: `#[Get('/login')]`, `#[Post('/login')]`, `#[Get('/register')]`, `#[Post('/register')]`, `#[Post('/logout')]`
- Login uses `$guard->attempt(['email' => ..., 'password' => ...])` which returns bool
- Registration creates user, hashes password, logs them in, dispatches UserRegisteredEvent
- Logout calls `$guard->logout()` and redirects to /login
- Validation rules: email required|email|unique, username required|alpha_num|min:3|max:50|unique, password required|string|min:8|confirmed
- GuestMiddleware on login/register routes, AuthMiddleware on logout
- CSRF protection via CsrfMiddleware on POST routes

## Requirements (Test Descriptions)
- [ ] `it shows the login form on GET /login`
- [ ] `it authenticates a user with valid credentials on POST /login`
- [ ] `it rejects login with invalid credentials`
- [ ] `it shows the registration form on GET /register`
- [ ] `it creates a new user on POST /register with valid data`
- [ ] `it rejects registration with duplicate email`
- [ ] `it rejects registration with password shorter than 8 characters`
- [ ] `it logs the user out on POST /logout and redirects to login`
- [ ] `it dispatches UserRegisteredEvent after successful registration`

## Acceptance Criteria
- AuthController at `app/user/src/Controller/AuthController.php`
- All routes match the spec exactly
- Password hashed before storage using HasherInterface
- Session regenerated after login
- Redirects: successful login → /, successful register → /, logout → /login
- Validation errors returned to form

## Implementation Notes
