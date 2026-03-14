# Task 004: Refactor SpaceRepository to use query builder

**Status**: complete
**Depends on**: none
**Retry count**: 0

## Description
Replace the single raw SQL method in SpaceRepository (`findActive()`) with the query builder API. This is the simplest refactor — one method with a single WHERE clause and manual hydration.

## Context
- Related files: `app/space/src/Repository/SpaceRepository.php`
- `findBySlug()` already uses `$this->findOneBy()` — no changes needed
- `findActive()` queries `WHERE is_archived = ?` with binding `[0]` — convert to `->where('is_archived', '=', false)`

## Requirements (Test Descriptions)
- [x] `it finds active spaces using query builder instead of raw SQL`

## Method-by-method conversion

### `findActive(): array`
```php
return $this->query()
    ->where('is_archived', '=', false)
    ->getEntities();
```

## Acceptance Criteria
- All requirements have passing tests
- No `$this->connection->query()` calls remain
- No manual `sprintf` or `$this->hydrator->hydrate()` calls
- Method signature and return type unchanged
