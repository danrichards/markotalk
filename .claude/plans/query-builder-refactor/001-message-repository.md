# Task 001: Refactor MessageRepository to use query builder

**Status**: completed
**Depends on**: none
**Retry count**: 0

## Description
Replace all raw SQL in MessageRepository with the framework's query builder API. Five methods use `$this->connection->query()` with `sprintf` for table names and manual hydration — convert each to `$this->query()->...->getEntities()`.

## Context
- Related files: `app/message/src/Repository/MessageRepository.php`
- Framework reference: `vendor/marko/database/src/Repository/Repository.php` (`query()` method), `vendor/marko/database/src/Repository/RepositoryQueryBuilder.php`
- `$this->query()` returns `RepositoryQueryBuilder` pre-configured with table name from entity metadata
- `->getEntities()` replaces `array_map` + `$this->hydrator->hydrate()` pattern
- The `save()` override and `findPaginated()` cursor logic stay the same structurally, just use query builder for the DB call

## Requirements (Test Descriptions)
- [ ] `it finds pinned messages by space using query builder instead of raw SQL`
- [ ] `it finds messages by space with limit using query builder instead of raw SQL`
- [ ] `it finds paginated messages using query builder with conditional cursor where clause`
- [ ] `it finds messages by space since a given id using query builder`
- [ ] `it finds edited messages since a timestamp using query builder`

## Method-by-method conversion

### `findPinnedBySpace(int $spaceId): array`
```php
// Before: raw SQL with sprintf + manual hydration
// After:
return $this->query()
    ->where('space_id', '=', $spaceId)
    ->where('is_pinned', '=', true)
    ->orderBy('id', 'ASC')
    ->getEntities();
```

### `findBySpace(int $spaceId, int $limit = 50): array`
```php
return $this->query()
    ->where('space_id', '=', $spaceId)
    ->orderBy('id', 'ASC')
    ->limit($limit)
    ->getEntities();
```

### `findPaginated(int $spaceId, int $perPage = 50, ?string $cursor = null): CursorPaginator`
```php
// Build query with conditional cursor
$query = $this->query()
    ->where('space_id', '=', $spaceId)
    ->orderBy('id', 'DESC')
    ->limit($perPage + 1);

if ($cursorId !== null) {
    $query->where('id', '<', $cursorId);
}

$rows = $query->getEntities();
// Rest of pagination logic (hasMore, reverse, CursorPaginator) stays the same
```

### `findBySpaceSince(int $spaceId, int $sinceId): array`
```php
return $this->query()
    ->where('space_id', '=', $spaceId)
    ->where('id', '>', $sinceId)
    ->orderBy('id', 'ASC')
    ->getEntities();
```

### `findEditedSince(int $spaceId, DateTimeImmutable $since): array`
```php
return $this->query()
    ->where('space_id', '=', $spaceId)
    ->where('edited_at', '>', $since->format('Y-m-d H:i:s'))
    ->orderBy('id', 'ASC')
    ->getEntities();
```

## Test Updates Required

Existing tests that construct `MessageRepository` directly and assert SQL strings must be updated:

### `CursorPaginationTest.php`
- The `makePaginationRepository()` helper must provide a `QueryBuilderFactoryInterface` mock when constructing `MessageRepository`
- SQL assertions like `->toContain('space_id = ?')` must change to match query builder output format: `->toContain('"space_id"')` (quoted identifiers, `$N` positional params)
- Specifically affected assertions:
  - Line 130: `'ORDER BY id DESC'` becomes `'ORDER BY "id" DESC'`
  - Line 131: `'space_id = ?'` becomes `'"space_id" = $1'`
  - Line 149: `'id < ?'` becomes `'"id" < $2'` (or similar positional index)
- The mock `ConnectionInterface` approach still works since `PgSqlQueryBuilder` depends on `ConnectionInterface`
- **Alternative**: Provide a mock `QueryBuilderInterface` via the factory closure and assert calls on it, or switch to purely behavioral assertions (check return values, not SQL strings)

## Acceptance Criteria
- All requirements have passing tests
- All existing tests in `CursorPaginationTest.php` pass with updated assertions
- No `$this->connection->query()` or `sprintf` calls remain
- No `$this->hydrator->hydrate()` calls remain
- Method signatures and return types unchanged
- `save()` override is untouched (it correctly uses `parent::save()`)
