# Project Overview

## Project Name
MarkoTalk

## Description
A real-time community chat platform built with the Marko PHP framework. Designed as the official gathering place for Marko developers — dogfooding the framework for its own community. Think Slack or Discord, but simpler, focused, and built entirely on Marko.

## Tech Stack
- Framework: Marko PHP Framework (local at `~/Sites/marko`)
- Language: PHP 8.5+
- Database: MySQL
- Template Engine: Latte 3.0 (via `marko/view-latte`)
- Real-Time: SSE via `marko/sse`
- Testing: Pest PHP 4.3
- Linting: PHP-CS-Fixer + PHPCS (Slevomat) + Rector
- Client-Side: Vanilla JavaScript (no framework)

## Project Type
Real-time web application (community chat platform)

## Goals
1. **Dogfood Marko** — prove the framework works for a real, non-trivial app
2. **Showcase extensibility** — demonstrate plugins, preferences, events, and modules in a natural context
3. **Real-time messaging** — messages appear instantly via SSE, proving PHP can do real-time
4. **Keep it focused** — a great chat experience, not a feature-bloated clone

## Reference Projects
- **Marko Framework**: `~/Sites/marko` — the framework source, read its CLAUDE.md and conventions
- **MyBlog Skeleton**: `~/Sites/myblog` — reference project showing exact setup patterns
- **Full Spec**: `~/Sites/markotalk.md` — complete project specification with entities, routes, and build order

## Key Dependencies (Marko Packages)
All referenced via path repositories to `../marko/packages/*`:
- `marko/core` — Module system, DI, plugins, events, preferences
- `marko/routing` — HTTP routing, request/response, middleware
- `marko/sse` — StreamingResponse, SseEvent, SseStream
- `marko/database` + `marko/database-mysql` — Entity definitions, MySQL driver
- `marko/view` + `marko/view-latte` — Latte template engine
- `marko/authentication` — Login, logout, guards
- `marko/authorization` — Gates, policies, permissions
- `marko/notification` + `marko/notification-database` — Notification system
- `marko/pagination` — Cursor pagination for message history
- `marko/session-file` — File-based sessions
- `marko/validation` — Input validation
- `marko/security` — CSRF middleware
- `marko/hashing` — Password hashing
