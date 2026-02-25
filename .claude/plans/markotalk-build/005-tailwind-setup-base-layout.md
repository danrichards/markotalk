# Task 005: Tailwind Setup and Base Layout Template

**Status**: completed
**Depends on**: 001
**Retry count**: 0

## Description
Set up Tailwind CSS standalone CLI with the semantic @apply architecture defined in `.claude/styling.md`. Create the base layout template, CSS entry point, and all CSS layer files. Download the Tailwind standalone binary and configure tailwind.config.js.

## Context
- Tailwind standalone CLI binary — no Node.js required
- All styling via @apply in CSS files — NO utility classes in .latte templates (see `.claude/styling.md`)
- CSS structure: src/css/app.css (entry), base.css, layout.css, components.css, modules/*.css
- Template: app-level layout at a location accessible to all modules
- Layout structure: app-shell > app-header + app-body (sidebar + main-content)
- Design tokens in tailwind.config.js for brand colors, sidebar width, header height
- Content paths in config must scan both `app/*/resources/views/**/*.latte` and `src/css/**/*.css`

## Requirements (Test Descriptions)
- [ ] `it has a tailwind.config.js with correct content paths`
- [ ] `it has src/css/app.css entry point importing all layers`
- [ ] `it has src/css/base.css with reset and typography styles`
- [ ] `it has src/css/layout.css with app-shell, sidebar, main-content, app-header classes`
- [ ] `it has src/css/components.css with btn, btn-primary, form-field, form-label classes`
- [ ] `it has the base layout.latte with structural HTML using only semantic class names`
- [ ] `it compiles to public/css/app.css via Tailwind CLI`

## Acceptance Criteria
- `./tailwindcss -i src/css/app.css -o public/css/app.css` produces valid CSS
- Layout template has app-shell with header, sidebar, and main-content areas
- No Tailwind utility classes appear in any .latte file
- All CSS classes use @apply for styling
- Layout includes `{block sidebar}`, `{block content}`, `{block head}` blocks
- Layout links to `/css/app.css` and `/js/app.js`

## Implementation Notes
