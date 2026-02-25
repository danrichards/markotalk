# Task 001: Project Skeleton and Bootstrap

**Status**: completed
**Depends on**: none
**Retry count**: 0

## Description
Create the MarkoTalk project skeleton: root composer.json with path repositories to all required marko packages, public/index.php bootstrap, .env/.env.example, config files, and the empty directory structure for all modules. This is the foundation everything else builds on.

## Context
- Reference: `~/Sites/myblog` for exact composer.json, index.php, and directory patterns
- Reference: `~/Sites/marko/CLAUDE.md` for framework conventions
- All marko packages use path repositories to `../marko/packages/*` pinned to `dev-develop as 0.1.0`
- Bootstrap uses `require __DIR__ . '/../vendor/marko/core/bootstrap.php'` with named params

## Requirements (Test Descriptions)
- [ ] `it has a composer.json with all required marko package dependencies`
- [ ] `it has path repositories for all marko packages referenced`
- [ ] `it has a public/index.php that bootstraps the Marko application`
- [ ] `it has a .env.example with all required environment variables`
- [ ] `it has config/database.php with MySQL configuration`
- [ ] `it has config/markotalk.php with app-level configuration`
- [ ] `it has the correct directory structure for all five app modules`

## Acceptance Criteria
- `composer install` succeeds without errors
- All five module directories exist: app/user, app/space, app/message, app/notification, app/admin
- Each module has a composer.json with `extra.marko.module: true`
- public/index.php follows the myblog bootstrap pattern exactly
- config/markotalk.php includes max_message_length, presence_timeout, sse_poll_interval, sse_heartbeat_interval, sse_timeout, default_spaces

## Implementation Notes
