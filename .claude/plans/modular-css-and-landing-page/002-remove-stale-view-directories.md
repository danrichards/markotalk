# Task 002: Remove Stale Empty View Directories

**Status**: complete
**Depends on**: none
**Retry count**: 0

## Description
Remove the empty subdirectories in `resources/views/` that were left behind when templates moved into module directories. These directories (`auth/`, `message/`, `notification/`, `profile/`, `space/`) are empty and misleading — the actual templates live at `app/{module}/resources/views/`.

## Context
- All templates already live at `app/{module}/resources/views/`
- The only file that should remain in `resources/views/` is `layout.latte` (the base layout)
- The landing page template (Task 004) will also live here as an app-level view
- Related files:
  - `resources/views/layout.latte` — must NOT be deleted
  - `resources/views/auth/` — empty directory
  - `resources/views/message/` — empty directory
  - `resources/views/notification/` — empty directory
  - `resources/views/profile/` — empty directory
  - `resources/views/space/` — empty directory

## Requirements (Test Descriptions)
- [x] `it removes the empty auth/ directory from resources/views/`
- [x] `it removes the empty message/ directory from resources/views/`
- [x] `it removes the empty notification/ directory from resources/views/`
- [x] `it removes the empty profile/ directory from resources/views/`
- [x] `it removes the empty space/ directory from resources/views/`
- [x] `it preserves layout.latte in resources/views/`

## Acceptance Criteria
- No empty subdirectories remain in `resources/views/`
- `resources/views/layout.latte` is untouched
- No templates in module directories are affected

## Implementation Notes
All five empty directories (`auth/`, `message/`, `notification/`, `profile/`, `space/`) were removed from `resources/views/` using `rmdir`. The `layout.latte` file was verified before and after — it remains intact and unchanged. No PHP code or tests were needed for this cleanup task.
