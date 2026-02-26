# MarkoTalk

A real-time community chat platform built with the Marko PHP framework. Dogfooding Marko for its own developer community — showcasing plugins, preferences, events, and modules.

## Tech Stack

- **Language**: PHP 8.5+
- **Framework**: Marko PHP Framework (local at `~/Sites/marko`)
- **Database**: PostgreSQL
- **Templates**: Latte 3.0 (`marko/view-latte`)
- **Real-Time**: SSE (`marko/sse`)
- **Testing**: Pest PHP 4.3
- **Linting**: PHP-CS-Fixer + PHPCS (Slevomat) + Rector
- **Styling**: Tailwind CSS (standalone CLI) with semantic `@apply` classes
- **Client-Side**: Vanilla JavaScript (no framework)

## Core Principles

1. **Follow Marko conventions** — read `~/Sites/marko/CLAUDE.md` before writing code
2. **Loud errors** — no silent failures, always provide helpful messages with context and suggestion
3. **Explicit over implicit** — no magic, everything discoverable via constructor injection and attributes
4. **True modularity** — each feature is a self-contained module in `app/`
5. **Real features only** — no pseudo-functionality, stubs, or demonstration code

## Project Structure

- `app/` — Application modules: user, space, message, notification, admin
- `config/` — PHP config files (database.php, markotalk.php)
- `database/migrations/` — Sequential migration files
- `public/` — Entry point (index.php), CSS, JS, storage
- `resources/views/` — Latte templates

## Commands

```bash
# Run tests (parallel)
./vendor/bin/pest --parallel

# Run tests (sequential)
./vendor/bin/pest

# Lint check
./vendor/bin/phpcs --standard=phpcs.xml

# Lint fix
./vendor/bin/php-cs-fixer fix && ./vendor/bin/phpcbf

# Static analysis
./vendor/bin/rector process
```

## Key Rules

- `declare(strict_types=1);` on every PHP file
- Type declarations required on all parameters, returns, and properties
- Constructor injection only — no service locator, no `new` for services
- No final classes, no traits, no magic methods
- Use attributes for metadata (`#[Plugin]`, `#[Observer]`, `#[Get]`, `#[Post]`, etc.)
- Named parameters in all function calls
- All exceptions extend `MarkoException` with `message`, `context`, `suggestion`
- Pest-style tests with present tense verbs and chained expectations
- Modules use `"extra": { "marko": { "module": true } }` in their composer.json
- Path repositories reference `../marko/packages/*` — all packages pinned to `dev-develop as 0.1.0`
- Reference `~/Sites/myblog` for exact project setup patterns
- Full spec at `~/Sites/markotalk.md` — follow the 6-phase build order
- No need to rebuild CSS, as it's watched and re-compiled on the fly

## Detailed Configuration

Project configuration files are in `.claude/`:
- `project-overview.md` — Project identity and dependencies
- `architecture.md` — Module structure, patterns, and build order
- `testing.md` — Test configuration, TDD workflow, and conventions
- `code-standards.md` — Coding conventions and linting setup
- `styling.md` — Tailwind CSS approach, naming conventions, and template rules
