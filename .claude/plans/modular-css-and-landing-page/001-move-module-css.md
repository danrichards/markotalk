# Task 001: Move Module CSS to Module Directories

**Status**: completed
**Depends on**: none
**Retry count**: 0

## Description
Move each module's CSS file from the centralized `src/css/modules/` directory into its respective module at `app/{module}/src/css/`. Update the Tailwind config content paths and the `src/css/app.css` import statements to point to the new locations. Delete the now-empty `src/css/modules/` directory.

## Context
- Marko's philosophy is extreme modularity — CSS should live with its module, just like templates already do at `app/{module}/resources/views/`
- The shared/global CSS files (`base.css`, `layout.css`, `components.css`) stay in `src/css/` — they're app-level concerns
- The Tailwind standalone CLI compiles `src/css/app.css` into `public/css/app.css`
- Related files:
  - `src/css/app.css` — entry point with `@import` statements
  - `src/css/modules/*.css` — 6 module CSS files to move
  - `tailwind.config.js` — content paths for class scanning
- Patterns to follow: Module views already live at `app/{module}/resources/views/`

## File Moves
- `src/css/modules/auth.css` → `app/user/src/css/auth.css`
- `src/css/modules/user.css` → `app/user/src/css/user.css`
- `src/css/modules/space.css` → `app/space/src/css/space.css`
- `src/css/modules/message.css` → `app/message/src/css/message.css`
- `src/css/modules/notification.css` → `app/notification/src/css/notification.css`
- `src/css/modules/admin.css` → `app/admin/src/css/admin.css`

## Requirements (Test Descriptions)
- [ ] `it places auth.css and user.css in app/user/src/css/ directory`
- [ ] `it places space.css in app/space/src/css/ directory`
- [ ] `it places message.css in app/message/src/css/ directory`
- [ ] `it places notification.css in app/notification/src/css/ directory`
- [ ] `it places admin.css in app/admin/src/css/ directory`
- [ ] `it updates app.css imports to reference new module CSS paths`
- [ ] `it adds app/*/src/css/**/*.css to tailwind.config.js content array`
- [ ] `it removes the src/css/modules/ directory entirely`
- [ ] `it does not modify the content of any CSS file during the move`

## Acceptance Criteria
- All module CSS files exist at their new locations
- `src/css/modules/` directory no longer exists
- `src/css/app.css` imports point to `../../app/{module}/src/css/{file}.css`
- `tailwind.config.js` content array includes `'./app/*/src/css/**/*.css'`
- No CSS file contents were modified (only moved)

## Implementation Notes
(Left blank - filled in by programmer during implementation)
