# Task 001: Add pub/sub packages and configuration

**Status**: completed
**Depends on**: none
**Retry count**: 0

## Description
Add the `marko/pubsub`, `marko/pubsub-pgsql`, and `marko/amphp` packages to the project and create the necessary configuration files. This establishes the foundation for all subsequent pub/sub work.

## Context
- Related files: `composer.json`, `config/pubsub.php` (new), `config/pubsub-pgsql.php` (new), `config/amphp.php` (new)
- Patterns to follow: Existing config files (`config/database.php`, `config/markotalk.php`) use `$_ENV` with defaults
- Path repositories follow pattern: `{ "type": "path", "url": "../marko/packages/{name}" }`
- Package version pinning: `"dev-develop as 0.1.0"`
- Package configs from framework: `~/Sites/marko/packages/pubsub/config/pubsub.php`, `~/Sites/marko/packages/pubsub-pgsql/config/pubsub-pgsql.php`, `~/Sites/marko/packages/amphp/config/amphp.php`

## Requirements (Test Descriptions)

- [ ] `it includes marko/pubsub in composer.json require`
- [ ] `it includes marko/pubsub-pgsql in composer.json require`
- [ ] `it includes marko/amphp in composer.json require`
- [ ] `it has path repositories for pubsub, pubsub-pgsql, and amphp packages`
- [ ] `it has a pubsub config file with driver set to pgsql and prefix`
- [ ] `it has a pubsub-pgsql config file with host, port, user, password, and database`
- [ ] `it has an amphp config file with shutdown timeout`

## Acceptance Criteria
- All requirements have passing tests
- `composer update` completes successfully
- Config files follow existing project conventions (env vars with defaults)
- The `pubsub.driver` default is `pgsql` (not `redis` as in the framework default)

## Implementation Notes
(Left blank - filled in by programmer during implementation)
