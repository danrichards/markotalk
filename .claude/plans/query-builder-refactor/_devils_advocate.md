# Devil's Advocate Review: query-builder-refactor

## Critical (Must fix before building)

### C1. `queryBuilderFactory` is never wired -- `$this->query()` will throw at runtime (ALL TASKS)

The `Repository` base class constructor accepts `?Closure $queryBuilderFactory = null`. The framework's DI container (`Container.php` lines 166-170) explicitly handles `Closure` parameters by falling back to their default value. Since the default is `null`, all repositories get `null` for this parameter.

Calling `$this->query()` when `queryBuilderFactory` is null throws `RepositoryException::queryBuilderNotConfigured()`. No repository in markotalk currently calls `$this->query()`, and no binding anywhere provides a `queryBuilderFactory` closure. The reference project (`myblog`) also never uses it.

**Every single task in this plan will fail at runtime.**

**Fix:** Add a prerequisite task (task 000) that wires the `queryBuilderFactory` in each module's `module.php` bindings. Each repository binding needs to become a closure that injects the factory. Example:

```php
MessageRepositoryInterface::class => function (ContainerInterface $container) {
    return new MessageRepository(
        connection: $container->get(ConnectionInterface::class),
        metadataFactory: $container->get(EntityMetadataFactory::class),
        hydrator: $container->get(EntityHydrator::class),
        queryBuilderFactory: fn () => $container->get(QueryBuilderInterface::class),
        eventDispatcher: $container->get(EventDispatcherInterface::class),
    );
},
```

All tasks 001-005 must depend on this task.

### C2. Existing tests assert raw SQL strings that will change after refactor (tasks 001, 002, 005)

The plan says "Out of Scope: Modifying entities, controllers, or tests" but multiple existing tests inspect SQL strings produced by the repository:

- `CursorPaginationTest.php` lines 130-132: asserts `'space_id = ?'` and `'ORDER BY id DESC'` -- query builder will produce `"space_id" = $1` and `ORDER BY "id" DESC`
- `CursorPaginationTest.php` line 149: asserts `'id < ?'`
- `ReactionTest.php` lines 67-68: asserts `'message_id = ?'`
- `ReactionTest.php` lines 95-97: asserts `'GROUP BY emoji'`
- `ReactionTest.php` lines 133-136: asserts `'DELETE FROM reactions'`, `'message_id = ?'`, `'user_id = ?'`, `'emoji = ?'`

After refactor, the query builder generates PostgreSQL-style SQL with quoted identifiers (`"column"`) and `$N` positional parameters instead of `?`. These assertions will all fail.

Furthermore, the tests create repositories without providing `queryBuilderFactory`, so any test that exercises a method now using `$this->query()` will throw `RepositoryException::queryBuilderNotConfigured()`.

**Fix:** Bring test updates into scope. Each task must update the corresponding tests to: (a) provide a `queryBuilderFactory` when constructing the repository, and (b) update SQL string assertions to match the query builder's output format (or switch to behavioral assertions that don't depend on SQL syntax).

### C3. `findGrouped` raw SQL uses `?` placeholders but `raw()` passes through to PgSqlQueryBuilder (task 002)

The current `findGrouped()` code uses `?` placeholders in its raw SQL, which works because it calls `$this->connection->query()` directly (PDO handles `?`). The plan converts this to `$this->query()->raw()`, which delegates to `PgSqlQueryBuilder::raw()`, which also calls `$this->connection->query()` -- so `?` placeholders will still work via PDO.

However, the `countUnread` method in task 005 has the same pattern. The plan's example code for `countUnread` shows using `?` placeholders with `$this->query()->raw()`, which is fine for the same reason.

**Not a blocker** -- the `?` placeholders work through PDO regardless of the query builder wrapper. No fix needed here.

## Important (Should fix before building)

### I1. Task 005 `countUnread` snippet is incomplete -- missing the membership lookup (task 005)

The plan's conversion example for `countUnread` shows:
```php
$rows = $this->query()->raw(sql: $sql, bindings: [$spaceId, $membership->lastReadMessageId]);
```

But the variable `$membership` is not defined in the snippet. The actual method first calls `$this->findByUserAndSpace()` to get the membership, then checks for null/null lastReadMessageId before querying. The plan's snippet omits this critical context, which could mislead a worker into removing the guard clauses.

**Fix:** Update the task 005 snippet to include the full method body with guard clauses.

### I2. `RepositoryQueryBuilder` is `readonly` -- `$this->query()` creates a fresh instance each call (task 001)

In task 001's `findPaginated`, the plan shows building a query conditionally:
```php
$query = $this->query()
    ->where('space_id', '=', $spaceId)
    ->orderBy('id', 'DESC')
    ->limit($perPage + 1);

if ($cursorId !== null) {
    $query->where('id', '<', $cursorId);
}
```

This is fine because `RepositoryQueryBuilder` is `readonly` but its methods delegate to the mutable inner `$this->queryBuilder` and return `$this` (via `static`). The chaining works correctly. However, `PgSqlQueryBuilder` stores state (wheres, orders, etc.) as mutable properties that accumulate. Each call to `$this->query()` creates a fresh `PgSqlQueryBuilder` via the factory closure, so there's no cross-contamination. No issue here.

### I3. `where('is_pinned', '=', true)` may produce unexpected SQL for boolean columns on PostgreSQL (task 001, task 004)

The current code uses `'is_pinned = true'` (SQL literal) in task 001 and `bindings: [0]` for `is_archived` in task 004. The query builder's `where()` with a PHP `true`/`false` value will pass it through `PgSqlConnection::bindValues()` which uses `PDO::PARAM_BOOL`. PostgreSQL handles this correctly with PDO, so `->where('is_pinned', '=', true)` will work.

However, the current code for `findActive()` uses `[0]` (integer) as the binding for `is_archived`. If the column is actually a boolean in PostgreSQL, passing `false` (as the plan suggests) is more correct. If it's an integer column (0/1), then `false` with `PDO::PARAM_BOOL` could behave differently. Need to verify the column type.

**Fix:** Add a note in tasks 001 and 004 to verify that the column types in the migration are `BOOLEAN` (not `INTEGER`) and use the appropriate PHP boolean value.

### I4. Plan says "no dependencies between tasks" but task 000 (new) must come first (ALL TASKS)

After adding the prerequisite task for wiring queryBuilderFactory, the dependency graph changes. All tasks 001-005 must depend on task 000.

**Fix:** Update the task table in `_plan.md` to reflect this dependency.

### I5. Boolean column handling verified -- no issue (tasks 001, 004)

Migration files confirm `is_pinned` and `is_archived` are PostgreSQL `BOOLEAN` columns. The current `findActive()` uses `[0]` (integer binding), which is imprecise. The refactored `->where('is_archived', '=', false)` is correct and an improvement. No fix needed.

## Minor (Nice to address)

### M1. Task descriptions say "Test Descriptions" but describe implementation, not test behavior

The requirement test descriptions like "it finds pinned messages by space using query builder instead of raw SQL" describe HOW the code works internally. Better test descriptions would focus on behavior: "it finds pinned messages ordered by id ascending." The current tests already test behavior (return values), so the new "query builder instead of raw SQL" test descriptions may lead workers to write tests that assert implementation details.

### M2. `edited_at` date formatting is fragile (task 001)

The plan shows `$since->format('Y-m-d H:i:s')` being passed to the query builder's `where()`. The `PgSqlConnection::bindValues()` would handle a `DateTimeImmutable` as `PDO::PARAM_STR` via the `default` match arm. Passing the formatted string is fine but slightly redundant since PDO would stringify it anyway. Not a bug, just worth noting.

### M3. The `findByMessage` return type annotation says `Entity` not `Reaction` (task 002)

In `ReactionRepository::findByMessage()`, the `array_map` callback return type is `Entity` (line 33 of the source). After refactor to `getEntities()`, the returned type will still be `Entity` from the base hydrator. Not a regression, just a pre-existing type narrowness.

## Questions for the Team

### Q1. Should the framework's DI container be enhanced to auto-wire `queryBuilderFactory`?

Rather than manually wiring closures in every module, should the framework's container detect the `queryBuilderFactory` parameter pattern and auto-wire it? This would be a framework-level change but would eliminate the need for task 000 and prevent this issue for all future repositories.

### Q2. Should existing tests that assert SQL strings be migrated to behavioral-only assertions?

The current tests check both behavior (return values) AND implementation (SQL strings). After refactor, the SQL format changes. Should tests only assert behavior (correct entities returned, correct counts), or should they continue asserting SQL structure with the new format?

### Q3. Should `findPinnedBySpace` be added to test stub implementations?

`findPinnedBySpace` IS on `MessageRepositoryInterface`. Some test stubs (like `makePinMessageRepository`) don't implement it, but since that predates this refactor it's a pre-existing issue, not introduced by this plan.
