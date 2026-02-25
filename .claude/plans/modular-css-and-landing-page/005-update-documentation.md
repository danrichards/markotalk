# Task 005: Update Project Documentation

**Status**: pending
**Depends on**: 001, 003, 004
**Retry count**: 0

## Description
Update the project documentation to reflect the new CSS file locations, the new route structure, and the landing page addition. This ensures the docs stay accurate for future development.

## Context
- Module CSS moved from `src/css/modules/` to `app/{module}/src/css/`
- Root route changed: `GET /` is now the public landing page, `GET /home` is the authenticated redirect
- New app-level controller namespace added
- Related files:
  - `.claude/architecture.md` — directory structure diagram shows old CSS locations and old view structure
  - `.claude/styling.md` — CSS file organization section shows `src/css/modules/` structure
  - `CLAUDE.md` — may reference project structure

## Requirements (Test Descriptions)
- [ ] `it updates architecture.md directory structure to show module CSS at app/{module}/src/css/`
- [ ] `it updates architecture.md to remove empty view subdirectories from resources/views/`
- [ ] `it updates architecture.md to include app-level src/Controller/ directory`
- [ ] `it updates styling.md CSS file organization to show module CSS in module directories`
- [ ] `it updates architecture.md routes section to reflect / as landing page and /home as authenticated redirect`

## Acceptance Criteria
- `.claude/architecture.md` directory structure is accurate
- `.claude/styling.md` CSS organization matches actual file locations
- No documentation references `src/css/modules/` (the deleted directory)
- Landing page and `/home` route are documented

## Implementation Notes
(Left blank - filled in by programmer during implementation)
