# Plan: Modular CSS & Public Landing Page

## Created
2026-02-25

## Status
completed

## Objective
Move module CSS files into their respective module directories to align with Marko's modularity philosophy, clean up stale empty directories, create a branded public landing page at `/`, and relocate the authenticated home redirect to `/home`.

## Scope

### In Scope
- Move 6 module CSS files from `src/css/modules/` into `app/{module}/src/css/`
- Update Tailwind config and CSS imports for new paths
- Delete stale empty subdirectories in `resources/views/`
- Create a public landing page at `/` with MarkoTalk branding and login/register CTAs
- Move authenticated redirect from `GET /` to `GET /home`
- Update all internal redirects from `/` to `/home`
- Update project documentation to reflect changes

### Out of Scope
- Redesigning existing authenticated pages
- Moving shared/global CSS (`base.css`, `layout.css`, `components.css`) — these remain app-level
- Adding new features to the landing page beyond branding and CTAs
- Moving JS files into modules (separate effort)

## Success Criteria
- [ ] Module CSS files live at `app/{module}/src/css/{module}.css`
- [ ] `src/css/modules/` directory is deleted
- [ ] Empty subdirectories in `resources/views/` are removed
- [ ] Tailwind builds successfully with new CSS paths
- [ ] Compiled `public/css/app.css` output is functionally identical
- [ ] `GET /` shows a branded landing page for unauthenticated visitors
- [ ] `GET /home` redirects authenticated users to their first space
- [ ] Login/register redirects point to `/home`
- [ ] All tests passing
- [ ] Documentation reflects new structure

## Task Overview
| Task | Description | Depends On | Status |
|------|-------------|------------|--------|
| 001 | Move module CSS to module directories | - | completed |
| 002 | Remove stale empty view directories | - | completed |
| 003 | Relocate authenticated home route to /home | - | completed |
| 004 | Create public landing page at / | 003 | completed |
| 005 | Update project documentation | 001, 003, 004 | completed |

## Architecture Notes
- The landing page controller is **app-level** (not a module): lives at `src/Controller/LandingController.php` with namespace `App\Controller`
- Requires adding `"App\\Controller\\": "src/Controller/"` to `composer.json` PSR-4 autoload
- Landing page template goes in `resources/views/landing.latte` (app-level views directory)
- Landing page CSS goes in `src/css/landing.css` (app-level, imported in `app.css`)
- Shared CSS (`base.css`, `layout.css`, `components.css`) stays in `src/css/` — they're app-level concerns
- The landing page should NOT use the authenticated layout (no sidebar/header) — use a minimal layout or standalone template

## Risks & Mitigations
- Tailwind build breaks after CSS move: Verify content paths in config match new locations
- Broken redirects after route change: Grep for all `/` redirects and update to `/home`
- AuthMiddleware redirect behavior: Verify Marko's AuthMiddleware redirects to `/login` (not `/`), so the landing page won't create a redirect loop
