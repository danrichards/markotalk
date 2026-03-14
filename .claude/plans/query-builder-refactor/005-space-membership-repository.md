# Task 005: Refactor SpaceMembershipRepository to use query builder and save()

**Status**: completed
**Depends on**: none
**Retry count**: 0

## Description
Replace raw SQL in SpaceMembershipRepository with query builder and `$this->save()`. Five methods need conversion: two simple WHERE queries, one cross-table count, one single-entity update, and one bulk update.

## Context
- Related files: `app/space/src/Repository/SpaceMembershipRepository.php`
- `findByUserAndSpace()` already uses `$this->findOneBy()` — no changes needed
- `countUnread()` queries the `messages` table (not this repo's table) — use `$this->query()->raw()` or `$this->connection->query()` since query builder is pre-bound to `space_memberships`
- `updateLastReadMessageId()` mutates entity then runs raw UPDATE — use `$this->save()` instead
- `clearLastReadMessageId()` is a bulk update (no specific entity) — use `$this->query()->where()->update()`

## Requirements (Test Descriptions)
- [ ] `it finds all memberships for a user using query builder`
- [ ] `it finds all memberships for a space using query builder`
- [ ] `it counts unread messages using raw query for cross-table access`
- [ ] `it updates last read message id using save instead of raw SQL`
- [ ] `it clears last read message id using query builder bulk update`

## Method-by-method conversion

### `findAllForUser(int $userId): array`
```php
return $this->query()
    ->where('user_id', '=', $userId)
    ->getEntities();
```

### `findAllForSpace(int $spaceId): array`
```php
return $this->query()
    ->where('space_id', '=', $spaceId)
    ->getEntities();
```

### `countUnread(int $userId, int $spaceId): int`
```php
// Keep the existing guard clauses — they are critical
$membership = $this->findByUserAndSpace(userId: $userId, spaceId: $spaceId);

if ($membership === null || $membership->lastReadMessageId === null) {
    return 0;
}

// Cross-table query — use raw() since we're querying the messages table, not space_memberships
$sql = 'SELECT COUNT(*) as count FROM messages WHERE space_id = ? AND id > ?';
$rows = $this->query()->raw(sql: $sql, bindings: [$spaceId, $membership->lastReadMessageId]);

return (int) ($rows[0]['count'] ?? 0);
```

### `updateLastReadMessageId(SpaceMembership $membership, int $messageId): void`
```php
$membership->lastReadMessageId = $messageId;
$this->save(entity: $membership);
```

### `clearLastReadMessageId(int $messageId): void`
```php
$this->query()
    ->where('last_read_message_id', '=', $messageId)
    ->update(['last_read_message_id' => null]);
```

## Test Updates Required

Existing tests in `UnreadCountsTest.php` that construct `SpaceMembershipRepository` directly must be updated:

### `UnreadCountsTest.php`
- The `createUnreadRepository()` helper and inline repository constructions must provide a `QueryBuilderFactoryInterface` mock
- The `countUnread` tests use a multi-call mock connection (first call returns membership, second returns count). After refactor:
  - `findByUserAndSpace` still uses `$this->findOneBy()` which calls `$this->connection->query()` directly (not via query builder), so the first query call is unchanged
  - `countUnread` now uses `$this->query()->raw()` which delegates to `connection->query()` -- the second query call still works the same way since `raw()` passes through
  - The tests should still work without SQL assertion changes, but the repository constructor needs `queryBuilderFactory`
- Methods `findAllForUser` and `findAllForSpace` now use `$this->query()` so any tests exercising those methods need the factory too

## Acceptance Criteria
- All requirements have passing tests
- All existing tests in `UnreadCountsTest.php` pass
- No `$this->connection->query()` or `$this->connection->execute()` calls remain
- No manual `sprintf` or `$this->hydrator->hydrate()` calls
- Cross-table query uses `->raw()` (acceptable)
- Method signatures and return types unchanged
