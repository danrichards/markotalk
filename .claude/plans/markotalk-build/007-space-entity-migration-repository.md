# Task 007: Space Entity, Migration, and Repository

**Status**: completed
**Depends on**: 001
**Retry count**: 0

## Description
Create the Space entity with all fields from the spec, its migration, repository interface, repository implementation, and module.php bindings. Spaces are the rooms/channels where messages are posted.

## Context
- Entity: extend Entity, use #[Table('spaces')], #[Column] attributes
- Fields: id, name, slug, description, is_archived, created_by (FK users.id), created_at, updated_at
- Repository needs: findBySlug, findActive (non-archived), findAll
- Space module at app/space/ with its own composer.json, module.php, src/
- Reference PostRepository pattern for implementation

## Requirements (Test Descriptions)
- [ ] `it creates a Space entity with all required fields`
- [ ] `it has a migration that creates the spaces table with all columns and indexes`
- [ ] `it finds a space by slug`
- [ ] `it finds all active (non-archived) spaces`
- [ ] `it saves a new space with name, slug, description, and created_by`
- [ ] `it has module.php with SpaceRepositoryInterface binding`

## Acceptance Criteria
- Space entity at `app/space/src/Entity/Space.php`
- SpaceRepositoryInterface at `app/space/src/Repository/SpaceRepositoryInterface.php`
- SpaceRepository at `app/space/src/Repository/SpaceRepository.php`
- Migration at `database/migrations/20260224000002_create_spaces.php`
- module.php at `app/space/module.php`
- Slug is unique, name is unique

## Implementation Notes
