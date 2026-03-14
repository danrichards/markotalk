# Task 003: Refactor UserRepository to use save()

**Status**: completed
**Depends on**: none
**Retry count**: 0

## Description
Replace raw SQL UPDATE statements in UserRepository with `$this->save()`. Two methods (`updateLastSeen`, `clearLastSeen`) manually execute UPDATE SQL after mutating the entity — the framework's `save()` with dirty tracking handles this automatically.

## Context
- Related files: `app/user/src/Repository/UserRepository.php`
- `$this->save()` uses dirty tracking: it detects which properties changed and generates a minimal UPDATE
- `updateLastSeen()` already mutates `$user->lastSeenAt` before the raw SQL — just call `save()` instead
- `clearLastSeen()` already sets `$user->lastSeenAt = null` — just call `save()` instead
- `findByEmail()`, `findByUsername()`, `findByRememberToken()` already use framework methods (`findOneBy`, `find`) — no changes needed
- `updateRememberToken()` already uses `$this->save()` — no changes needed
- **Risk**: `save()` dispatches EntityUpdating/EntityUpdated events. Verify no observers cause unwanted side effects for user updates.

## Requirements (Test Descriptions)
- [ ] `it updates last seen timestamp using save instead of raw SQL`
- [ ] `it clears last seen timestamp using save instead of raw SQL`

## Method-by-method conversion

### `updateLastSeen(User $user, DateTimeImmutable $timestamp): void`
```php
// Before: raw UPDATE SQL
// After:
$user->lastSeenAt = $timestamp;
$this->save(entity: $user);
```

### `clearLastSeen(User $user): void`
```php
// Before: raw UPDATE SQL
// After:
$user->lastSeenAt = null;
$this->save(entity: $user);
```

## Acceptance Criteria
- All requirements have passing tests
- No `$this->connection->execute()` calls remain in UserRepository
- Method signatures unchanged
- Entity mutation happens before save (same as current pattern)
