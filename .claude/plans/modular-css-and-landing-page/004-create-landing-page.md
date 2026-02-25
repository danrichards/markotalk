# Task 004: Create Public Landing Page at /

**Status**: completed
**Depends on**: 003
**Retry count**: 0

## Description
Create a branded public landing page at `GET /` that welcomes visitors with information about MarkoTalk and provides clear login/register CTAs. This is an app-level controller (not a module) since it's a simple public-facing page. Authenticated users who visit `/` should be redirected to `/home`.

## Context
- The landing page is app-level: controller at `src/Controller/LandingController.php`, namespace `App\Controller`
- Requires adding `"App\\Controller\\": "src/Controller/"` to `composer.json` autoload PSR-4
- Template goes in `resources/views/landing.latte` — does NOT extend the authenticated layout (no sidebar/header)
- Landing page CSS goes in `src/css/landing.css` with semantic `@apply` classes, imported in `src/css/app.css`
- Should use the existing brand color (`#4f46e5` / `bg-brand`) for consistency
- Related files:
  - `composer.json` — add PSR-4 autoload entry
  - `resources/views/layout.latte` — reference for base HTML structure (but landing page uses its own minimal layout)
  - `src/css/components.css` — existing `.btn`, `.btn-primary` classes to reuse
  - `tailwind.config.js` — brand colors already configured
- Patterns to follow: AuthController for route attributes, existing Latte template conventions

## Landing Page Content
- MarkoTalk logo/name prominently displayed
- Tagline: real-time community chat platform built with the Marko PHP framework
- Brief feature highlights (real-time messaging, spaces/channels, community-driven)
- Two prominent CTAs: "Sign In" (links to /login) and "Create Account" (links to /register)
- Clean, modern design using existing Tailwind theme and brand colors
- If user is already authenticated, redirect to `/home`

## Requirements (Test Descriptions)
- [ ] `it returns 200 for GET / without authentication`
- [ ] `it redirects authenticated users from / to /home`
- [ ] `it renders the landing page template with MarkoTalk branding`
- [ ] `it includes a link to /login on the landing page`
- [ ] `it includes a link to /register on the landing page`
- [ ] `it registers the App\Controller namespace in composer.json autoload`

## Acceptance Criteria
- `GET /` serves a branded landing page to unauthenticated visitors
- `GET /` redirects authenticated users to `/home`
- Landing page has semantic CSS classes with `@apply` (no Tailwind utilities in template)
- Landing page template does not use the authenticated sidebar/header layout
- CSS is in `src/css/landing.css` and imported in `src/css/app.css`
- Controller uses constructor injection and route attributes per project standards

## Implementation Notes
(Left blank - filled in by programmer during implementation)
