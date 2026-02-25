# Task 009: Space Controller (List, Show, Join, Leave)

**Status**: completed
**Depends on**: 008
**Retry count**: 0

## Description
Create SpaceController with routes for listing spaces, viewing a space (chat view), joining, and leaving. The index route (GET /) redirects to the first space or shows space list. All routes require authentication.

## Context
- Routes: GET / → index, GET /spaces/{slug} → show, POST /spaces/{slug}/join → join, POST /spaces/{slug}/leave → leave
- AuthMiddleware on all routes
- Index redirects to first active space if user has memberships
- Show loads space, checks membership, renders chat view shell
- Join creates SpaceMembership, Leave removes it
- Auto-join: when viewing a space, if not a member, auto-join

## Requirements (Test Descriptions)
- [ ] `it redirects GET / to the first joined space`
- [ ] `it shows the chat view for GET /spaces/{slug}`
- [ ] `it returns 404 for a non-existent space slug`
- [ ] `it joins a space on POST /spaces/{slug}/join`
- [ ] `it leaves a space on POST /spaces/{slug}/leave`
- [ ] `it requires authentication on all routes`

## Acceptance Criteria
- SpaceController at `app/space/src/Controller/SpaceController.php`
- All routes use #[Get] or #[Post] attributes with correct paths
- AuthMiddleware applied via #[Middleware] on class
- Join/leave redirect back to the space or space list
- Show action loads space, messages, and members for the view

## Implementation Notes
