# Plan: Query Builder Refactor

## Created
2026-03-13

## Status
completed

## Objective
Replace raw SQL (`$this->connection->query()` / `$this->connection->execute()`) in all markotalk repositories with the Marko framework's query builder (`$this->query()`) and `$this->save()` for entity updates, following the conventions shown in the framework tutorial.

## Scope

### In Scope
- Refactor all 5 repositories to use query builder and `$this->save()`
- Replace manual `$this->hydrator->hydrate()` calls with `->getEntities()` / `->firstEntity()`
- Use `->raw()` for queries that need GROUP BY or cross-table access
- Update existing tests that assert raw SQL strings or construct repositories without `QueryBuilderFactoryInterface`
- Maintain identical behavior and return types

### Out of Scope
- Changing repository interfaces or method signatures
- Adding new functionality
- Modifying entities or controllers
- Changing the base `Repository` class in the framework

## Success Criteria
- [ ] No raw SQL (`$this->connection->query()` / `$this->connection->execute()`) in any markotalk repository except via `->raw()` for complex aggregates
- [ ] No manual `$this->hydrator->hydrate()` calls in markotalk repositories
- [ ] All existing tests pass (`./vendor/bin/pest --parallel`)
- [ ] Code follows project standards (named parameters, strict types)

## Task Overview
| Task | Description | Depends On | Status |
|------|-------------|------------|--------|
| 001 | Refactor MessageRepository to use query builder + update tests | - | completed |
| 002 | Refactor ReactionRepository to use query builder + update tests | - | completed |
| 003 | Refactor UserRepository to use save() | - | completed |
| 004 | Refactor SpaceRepository to use query builder | - | completed |
| 005 | Refactor SpaceMembershipRepository to use query builder and save() + update tests | - | completed |

## Architecture Notes
- The framework now uses `QueryBuilderFactoryInterface` (auto-wired by DI) instead of `?Closure` — no manual wiring needed
- `$this->query()` returns a `RepositoryQueryBuilder` pre-configured with the entity's table name
- `->getEntities()` replaces manual `array_map` + `$this->hydrator->hydrate()` calls
- `->raw()` is available for queries the fluent API cannot express (GROUP BY, cross-table)
- `$this->save()` handles dirty tracking automatically — just mutate the entity and call save
- `PgSqlQueryBuilder` generates SQL with quoted identifiers (`"column"`) and `$N` positional parameters — existing tests that assert `?` placeholder SQL strings must be updated
- All 5 tasks are independent and can run in parallel

## Risks & Mitigations
- **Test breakage from SQL format changes**: `PgSqlQueryBuilder` produces different SQL than hand-written queries (quoted identifiers, `$N` params). Mitigated by updating tests alongside each repository refactor.
- Query builder may produce slightly different SQL: mitigated by running full test suite after each task
- `$this->save()` dispatches lifecycle events (EntityUpdating/EntityUpdated): verify no observers cause side effects for the update methods being refactored
- Tests constructing repositories must provide `QueryBuilderFactoryInterface` mock instead of closure
