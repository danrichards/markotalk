# Task 003: Relocate Authenticated Home Route to /home

**Status**: completed
**Depends on**: none
**Retry count**: 0

## Description
Change the authenticated "redirect to first space" route from `GET /` to `GET /home` in SpaceController. Update all internal redirects that currently point to `/` (for authenticated users) to point to `/home` instead. This frees up `GET /` for the public landing page.

## Context
- Currently `GET /` in SpaceController has AuthMiddleware and redirects to the user's first space
- Three places redirect to `/`: SpaceController::leave, AuthController::processLogin, AuthController::processRegister
- After this change, authenticated post-login flow goes to `/home` instead of `/`
- The Marko framework's AuthMiddleware redirects unauthenticated users to `/login` (not `/`), so no redirect loop risk
- Related files:
  - `app/space/src/Controller/SpaceController.php` — route attribute change on `index()` method
  - `app/user/src/Controller/AuthController.php` — two redirects to `/` on lines 63 and 118
  - `app/space/src/Controller/SpaceController.php` — one redirect to `/` on line 120 (leave method)

## Requirements (Test Descriptions)
- [ ] `it routes GET /home with AuthMiddleware to SpaceController::index`
- [ ] `it redirects to /home after successful login`
- [ ] `it redirects to /home after successful registration`
- [ ] `it redirects to /home after leaving a space`
- [ ] `it no longer routes GET / to SpaceController::index`

## Acceptance Criteria
- `GET /` is no longer handled by SpaceController
- `GET /home` behaves exactly as `GET /` did (AuthMiddleware + redirect to first space)
- All three redirect locations updated from `/` to `/home`
- No other files reference `redirect(url: '/')` for authenticated contexts

## Implementation Notes
(Left blank - filled in by programmer during implementation)
