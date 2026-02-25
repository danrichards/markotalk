# Task 002: User Entity and Migration

**Status**: completed
**Depends on**: 001
**Retry count**: 0

## Description
Create the User entity with all fields from the spec, implementing AuthenticatableInterface and AuthorizableInterface from the Marko framework. Create the corresponding migration using timestamp naming convention.

## Context
- Entity pattern: extend `Marko\Database\Entity\Entity`, use `#[Table]`, `#[Column]` attributes
- Must implement `Marko\Authentication\Contract\AuthenticatableInterface` (getAuthIdentifier, getAuthPassword, getRememberToken, etc.)
- Must implement `Marko\Authorization\Contract\AuthorizableInterface` (can method)
- Must implement `Marko\Notification\Contract\NotifiableInterface` (routeNotificationFor, getNotifiableId, getNotifiableType)
- Reference Post entity in `~/Sites/marko/packages/blog/src/Entity/Post.php` for pattern
- User role is a backed enum (UserRole: user, admin)
- Migration: anonymous class extending Migration, timestamp-prefixed filename

## Requirements (Test Descriptions)
- [ ] `it creates a User entity with all required fields`
- [ ] `it implements AuthenticatableInterface with correct methods`
- [ ] `it implements NotifiableInterface with correct methods`
- [ ] `it uses UserRole backed enum for the role field`
- [ ] `it has a migration that creates the users table with all columns and indexes`
- [ ] `it stores password as hashed string and role as enum value`

## Acceptance Criteria
- User entity at `app/user/src/Entity/User.php`
- UserRole enum at `app/user/src/Enum/UserRole.php`
- Migration at `database/migrations/20260224000001_create_users.php`
- All Column attributes match the spec schema (id, username, email, password, display_name, avatar_url, role, is_banned, last_seen_at, remember_token, created_at, updated_at)
- Entity uses `declare(strict_types=1)` and constructor property promotion

## Implementation Notes
