# Plan: MarkoTalk - Real-Time Community Chat Platform

## Created
2026-02-24

## Status
in_progress

## Objective
Build MarkoTalk — a real-time community chat platform using the Marko PHP framework. Follows the 6-phase build order from the spec, with corrections for framework API accuracy (class-based events, plugin naming conventions, module-namespaced templates, Tailwind semantic @apply styling).

## Scope

### In Scope
- Project skeleton (composer.json, bootstrap, config, .env, directory structure)
- User module (entity, registration, login, session auth, profiles, avatars)
- Space module (entity, list, view, join/leave, memberships)
- Message module (entity, send, edit, delete, cursor-paginated history)
- SSE real-time streaming (StreamController, EventSource client JS)
- Presence tracking (middleware + SSE heartbeat)
- Plugin system (MarkdownPlugin #[Before], MentionExtractorPlugin #[After])
- Event/Observer system (class-based events, decoupled observers)
- Notification module (mention notifications, welcome notifications, bell UI, mark read)
- Unread message counts per space
- Emoji reactions on messages
- Admin module (space CRUD, message moderation, user management)
- Authorization policies per module
- Tailwind CSS with semantic @apply classes (no utility classes in templates)
- Vanilla JS client (EventSource, DOM updates, scroll behavior, emoji picker)
- Rate limiting on message sends
- XSS sanitization for markdown HTML output
- Database seeders (default spaces, admin user)

### Out of Scope
- File upload for avatars (avatar_url is a text field for now — media upload is a future phase)
- Email notifications (mail-log driver for dev, no real email sending)
- WebSocket alternative to SSE
- Full-text search
- Thread/reply system (flat messages only)
- Dark mode (CSS architecture supports it, but not implemented)
- Mobile native app

## Success Criteria
- [ ] All 6 phases implemented and functional
- [ ] Real-time messages appear via SSE without page refresh
- [ ] Plugin interception works (markdown parsed before save, mentions extracted after save)
- [ ] Event/observer chain works (message.created → notification created → SSE push)
- [ ] Cursor pagination loads older messages on scroll-up
- [ ] All tests passing with ≥80% coverage
- [ ] Code follows Marko framework conventions and MarkoTalk code standards
- [ ] No Tailwind utility classes in any .latte template file

## Task Overview
| Task | Description | Depends On | Status |
|------|-------------|------------|--------|
| 001 | Project skeleton and bootstrap | - | completed |
| 002 | User entity and migration | 001 | completed |
| 003 | User repository and provider | 002 | pending |
| 004 | Auth controller (register, login, logout) | 003 | pending |
| 005 | Tailwind setup and base layout template | 001 | completed |
| 006 | Auth templates (login, register) | 004, 005 | pending |
| 007 | Space entity, migration, and repository | 001 | completed |
| 008 | Space membership entity and repository | 002, 007 | pending |
| 009 | Space controller (list, show, join, leave) | 008 | pending |
| 010 | Space templates (sidebar, chat view shell) | 005, 009 | pending |
| 011 | Message entity, migration, and repository | 007 | pending |
| 012 | Message controller (send, edit, delete, history) | 008, 011 | pending |
| 013 | Cursor-paginated message history | 012 | pending |
| 014 | Message template partial and chat feed | 010, 012 | pending |
| 015 | SSE stream controller | 011 | pending |
| 016 | Client-side EventSource JS | 014, 015 | pending |
| 017 | Presence tracking middleware | 003 | pending |
| 018 | Presence in SSE heartbeat and sidebar | 010, 015, 017 | pending |
| 019 | Event classes (MessageCreated, MentionDetected, UserRegistered) | 001 | completed |
| 020 | Markdown parser and MarkdownPlugin | 011, 019 | pending |
| 021 | MentionExtractorPlugin | 011, 019 | pending |
| 022 | Notification types (MentionNotification, WelcomeNotification) | 019 | pending |
| 023 | MentionNotificationObserver | 021, 022 | pending |
| 024 | WelcomeMessageObserver | 019, 009 | pending |
| 025 | NotificationStreamObserver (SSE push) | 015, 022 | pending |
| 026 | Notification controller and templates | 005, 022 | pending |
| 027 | Unread counts per space in sidebar | 008, 014 | pending |
| 028 | Emoji reactions (entity, controller, UI) | 012, 014 | pending |
| 029 | User profile controller and template | 003, 005 | pending |
| 030 | Reaction migration and repository | 011 | pending |
| 031 | Authorization policies (Message, Space, User) | 003, 008, 011 | pending |
| 032 | Admin middleware | 003 | pending |
| 033 | Admin space controller | 007, 032 | pending |
| 034 | Admin user controller | 003, 032 | pending |
| 035 | Admin templates | 005, 033, 034 | pending |
| 036 | Rate limiting on message sends | 012 | pending |
| 037 | Database seeders (default spaces, admin user) | 003, 007 | pending |
| 038 | Message pin controller action | 012, 031 | pending |
| 039 | Notification migration | 001 | completed |
| 040 | XSS sanitization for markdown output | 020 | pending |

## Architecture Notes

### Critical Framework API Corrections (vs. original spec)
1. **Plugin methods**: Use naming convention `beforeSave()`/`afterSave()`, NOT `method:` parameter. `#[Before(sortOrder: 10)]` not `#[Before(method: 'save')]`.
2. **Events are class-based**: `$dispatcher->dispatch(new MessageCreatedEvent(...))` not `dispatch('message.created', [...])`. All events extend `Marko\Core\Event\Event`.
3. **Observer attribute**: `#[Observer(event: MessageCreatedEvent::class)]` not string-based.
4. **Migration naming**: Timestamp-based `20260224000001_create_users.php`.
5. **Templates**: Module-namespaced `user::auth/login`, views in `app/{module}/resources/views/`.
6. **Before plugins**: Return `null` to pass through, non-null to short-circuit. Receive same args as target.
7. **After plugins**: Receive `(mixed $result, ...$originalArgs)`, must return result.

### Styling
- Tailwind standalone CLI, semantic @apply classes only
- NO Tailwind utility classes in .latte templates — see `.claude/styling.md`
- CSS organized by module: `src/css/modules/{module}.css`

### Module Bindings Pattern
Each module's `module.php` returns `['bindings' => [Interface::class => Implementation::class]]`.

## Risks & Mitigations
- **SSE connection limits**: Browser limits ~6 concurrent connections per domain. Mitigation: single SSE connection per user with multiplexed events, or accept per-space connections for simplicity in v1.
- **Plugin proxy overhead**: All repository calls go through PluginProxy. Mitigation: proxy only wraps classes with registered plugins (framework handles this automatically).
- **Latte template resolution for app modules**: Framework's ModuleLoader expects package-name-based paths. Mitigation: verify module name resolution works for `app/` modules early in Phase 1.
