# Task 003: User Repository and DatabaseUserProvider

**Status**: completed
**Depends on**: 002
**Retry count**: 0

## Description
Create UserRepository (extending Marko's Repository base) and DatabaseUserProvider (implementing UserProviderInterface) for authentication. Wire bindings in user module.php.

## Context
- Repository pattern: extend `Marko\Database\Repository\Repository`, set `ENTITY_CLASS`, implement custom interface
- UserProviderInterface from `marko/authentication`: retrieveById, retrieveByCredentials, validateCredentials, retrieveByRememberToken, updateRememberToken
- DatabaseUserProvider delegates to UserRepository for data access and uses HasherInterface for password verification
- module.php returns `['bindings' => [...]]` mapping interfaces to implementations
- Reference: PostRepository in `~/Sites/marko/packages/blog/src/Repositories/PostRepository.php`

## Requirements (Test Descriptions)
- [ ] `it finds a user by id`
- [ ] `it finds a user by email for credential lookup`
- [ ] `it finds a user by username`
- [ ] `it validates credentials against hashed password`
- [ ] `it retrieves user by remember token`
- [ ] `it updates remember token for a user`
- [ ] `it has module.php with correct interface-to-implementation bindings`

## Acceptance Criteria
- UserRepositoryInterface at `app/user/src/Repository/UserRepositoryInterface.php`
- UserRepository at `app/user/src/Repository/UserRepository.php`
- DatabaseUserProvider at `app/user/src/Provider/DatabaseUserProvider.php`
- module.php at `app/user/module.php` with bindings for UserRepositoryInterface and UserProviderInterface
- All methods use named parameters and strict types

## Implementation Notes
