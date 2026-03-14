# Task 002: Refactor ReactionRepository to use query builder

**Status**: completed
**Depends on**: none
**Retry count**: 0

## Description
Replace raw SQL in ReactionRepository with the query builder API. Three methods need conversion: `findByMessage()` to `->getEntities()`, `findGrouped()` to `->raw()` (complex aggregate), and `remove()` to `->delete()`.

## Context
- Related files: `app/message/src/Repository/ReactionRepository.php`
- `findGrouped()` uses GROUP BY with conditional aggregation — not expressible via fluent API, so use `$this->query()->raw()`
- `add()` already uses `$this->save()` — no changes needed
- `remove()` uses `$this->connection->execute()` with DELETE — convert to `$this->query()->where()->delete()`

## Requirements (Test Descriptions)
- [ ] `it finds reactions by message using query builder instead of raw SQL`
- [ ] `it finds grouped reactions using raw query through query builder`
- [ ] `it removes a reaction using query builder delete instead of raw SQL`

## Method-by-method conversion

### `findByMessage(int $messageId): array`
```php
return $this->query()
    ->where('message_id', '=', $messageId)
    ->getEntities();
```

### `findGrouped(int $messageId, int $userId): array`
```php
// Complex aggregate — use raw()
$sql = 'SELECT emoji, COUNT(*) as count, MAX(CASE WHEN user_id = ? THEN 1 ELSE 0 END) as user_reacted
         FROM ' . $this->metadata->tableName . ' WHERE message_id = ? GROUP BY emoji';

$rows = $this->query()->raw(sql: $sql, bindings: [$userId, $messageId]);

return array_map(
    callback: fn (array $row): array => [
        'emoji' => (string) $row['emoji'],
        'count' => (int) $row['count'],
        'user_reacted' => (bool) $row['user_reacted'],
    ],
    array: $rows,
);
```

### `remove(int $messageId, int $userId, string $emoji): void`
```php
$this->query()
    ->where('message_id', '=', $messageId)
    ->where('user_id', '=', $userId)
    ->where('emoji', '=', $emoji)
    ->delete();
```

## Test Updates Required

Existing tests in `ReactionTest.php` that construct `ReactionRepository` directly and assert SQL strings must be updated:

### `ReactionTest.php`
- The `createReactionMockConnectionWithHistory()` + direct `ReactionRepository` construction must provide a `QueryBuilderFactoryInterface` mock
- SQL assertions affected:
  - Line 67: `'message_id = ?'` becomes `'"message_id" = $1'` (for `findByMessage`)
  - Lines 133-136: `'DELETE FROM reactions'`, `'message_id = ?'`, `'user_id = ?'`, `'emoji = ?'` become quoted versions with `$N` params (for `remove`)
  - Lines 95-96: `'GROUP BY emoji'` assertion for `findGrouped` stays the same since it uses `->raw()` which passes SQL through unchanged
- The `findGrouped` test (lines 71-98) uses `->raw()` which passes through to `connection->query()` directly, so its SQL assertions remain valid with `?` placeholders
- **Alternative**: Switch to purely behavioral assertions where possible

## Acceptance Criteria
- All requirements have passing tests
- All existing tests in `ReactionTest.php` pass with updated assertions
- No `$this->connection->query()` or `$this->connection->execute()` calls remain
- No manual `sprintf` for table names
- `findGrouped()` still uses raw SQL via `->raw()` (acceptable for complex aggregates)
- Method signatures and return types unchanged
