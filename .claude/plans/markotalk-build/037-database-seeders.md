# Task 037: Database Seeders (Default Spaces, Admin User)

**Status**: completed
**Depends on**: 003, 007
**Retry count**: 0

## Description
Create database seeders to populate the initial state: default spaces (#general, #help, #showcase) and an admin user account. Uses the Marko Seeder pattern with #[Seeder] attribute.

## Context
- Seeder pattern: `#[Seeder(name: 'markotalk', order: 10)]`, implements SeederInterface, constructor injection
- Reference: BlogSeeder in `~/Sites/myblog/app/blog/Seed/BlogSeeder.php`
- Creates admin user: username 'admin', email from config or default, password hashed, role UserRole::Admin
- Creates default spaces from config/markotalk.php default_spaces array
- Admin user auto-joined to all default spaces
- Seeders run via CLI: `php vendor/bin/marko db:seed`

## Requirements (Test Descriptions)
- [ ] `it creates default spaces from config`
- [ ] `it creates an admin user with admin role`
- [ ] `it hashes the admin password`
- [ ] `it joins admin user to all default spaces`
- [ ] `it uses #[Seeder] attribute with correct name and order`

## Acceptance Criteria
- MarkoTalkSeeder at `app/user/Seed/MarkoTalkSeeder.php` (or shared location)
- Uses `#[Seeder(name: 'markotalk', order: 10)]`
- Creates spaces: general, help, showcase with proper slugs
- Creates admin user with hashed password
- SpaceMemberships created for admin to all spaces

## Implementation Notes
