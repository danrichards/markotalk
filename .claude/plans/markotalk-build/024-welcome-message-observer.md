# Task 024: WelcomeMessageObserver

**Status**: completed
**Depends on**: 019, 009
**Retry count**: 0

## Description
Create WelcomeMessageObserver that listens for UserRegisteredEvent and posts a welcome message to #general space. Also auto-joins the new user to all default spaces from config.

## Context
- Observer attribute: `#[Observer(event: UserRegisteredEvent::class)]`
- On user registration: auto-join user to all default_spaces from config
- Post a system message to #general: "Welcome @{username} to MarkoTalk!"
- System message uses a system user or the registered user's own id
- Loads space by slug from config default_spaces array
- Creates SpaceMembership for each default space

## Requirements (Test Descriptions)
- [ ] `it auto-joins new user to all default spaces`
- [ ] `it posts a welcome message to the general space`
- [ ] `it uses #[Observer(event: UserRegisteredEvent::class)]`
- [ ] `it reads default spaces from config/markotalk.php`
- [ ] `it handles missing general space gracefully`

## Acceptance Criteria
- WelcomeMessageObserver at `app/user/src/Observer/WelcomeMessageObserver.php`
- Uses `#[Observer(event: UserRegisteredEvent::class)]` attribute
- Constructor injects SpaceRepositoryInterface, SpaceMembershipRepositoryInterface, MessageRepositoryInterface, ConfigInterface
- Creates memberships and welcome message in handle()

## Implementation Notes
