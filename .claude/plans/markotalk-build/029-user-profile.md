# Task 029: User Profile Controller and Template

**Status**: completed
**Depends on**: 003, 005
**Retry count**: 0

## Description
Create ProfileController for viewing and editing user profiles. Users can update their display_name and avatar_url (text field for now — file upload is out of scope). Profile page uses semantic CSS classes.

## Context
- Routes: GET /profile → edit, POST /profile → update
- Edit shows current user data in a form
- Update validates and saves changes to display_name, avatar_url
- Validation: display_name required|string|max:100, avatar_url nullable|url
- AuthMiddleware on all routes
- Template at app/user/resources/views/profile/edit.latte

## Requirements (Test Descriptions)
- [ ] `it shows the profile edit form with current user data`
- [ ] `it updates display_name on valid submission`
- [ ] `it updates avatar_url on valid submission`
- [ ] `it validates display_name is required and max 100 characters`
- [ ] `it requires authentication`

## Acceptance Criteria
- ProfileController at `app/user/src/Controller/ProfileController.php`
- Template at `app/user/resources/views/profile/edit.latte`
- CSS: profile-form, profile-field, profile-avatar classes in a profile section of components.css or auth.css
- Form shows success message on save
- All styling via @apply, no utility classes in template

## Implementation Notes
