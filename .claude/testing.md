# Testing Configuration

## Test Framework
Pest PHP 4.3 (on PHPUnit 12)

## TDD Methodology

Each task follows strict Red -> Green -> Refactor:

1. Write failing test for one requirement
2. Write minimum code to pass
3. Refactor while tests stay green
4. Repeat for next requirement
5. Commit when task complete

## Commands
```bash
# Run all tests (parallel — default)
./vendor/bin/pest --parallel

# Run all tests (sequential, for debugging failures)
./vendor/bin/pest

# Run specific test file
./vendor/bin/pest app/user/tests/Unit/UserTest.php

# Run specific test by name
./vendor/bin/pest --filter="test name" --bail

# Run tests for a specific module
./vendor/bin/pest app/message/tests/ --parallel

# Run with coverage
./vendor/bin/pest --coverage --min=80

# Lint check
./vendor/bin/phpcs --standard=phpcs.xml

# Lint fix
./vendor/bin/php-cs-fixer fix && ./vendor/bin/phpcbf

# Static analysis
./vendor/bin/rector process
```

## Parallel Execution
- **Default**: Always run tests in parallel unless debugging a specific failure
- Parallel command: `./vendor/bin/pest --parallel`
- Sequential fallback: `./vendor/bin/pest` (use only when parallel causes flaky failures)

## Test File Locations
- Unit tests: `app/{module}/tests/Unit/`
- Feature/Integration tests: `app/{module}/tests/Feature/`

## Coverage Requirements
- Minimum: 80%
- Critical paths (auth, message delivery, SSE): >90%
- New code must have tests

## Test Naming Convention
- Test files: `{Name}Test.php`
- Test methods: Pest-style `it('does something')` or `test('something')`
- Present tense verbs: "resolves", "throws", "returns", "sends"
- Use `describe()` blocks for grouping related tests
- Chain expectations with `->and()` — no consecutive `expect()` calls

## Test Patterns
- Use `beforeEach()` for shared setup
- Mock external dependencies (database, SSE streams)
- Test plugins by verifying before/after behavior on repository methods
- Test observers by dispatching events and asserting side effects
- Test policies by asserting authorization checks pass/fail for different roles
