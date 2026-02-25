# Task 012: Message Controller (Send, Edit, Delete, History)

**Status**: completed
**Depends on**: 008, 011
**Retry count**: 0

## Description
Create MessageController with routes for sending messages, editing, deleting, and fetching paginated history. Send validates input, creates message, and returns redirect or JSON. Edit/delete check ownership.

## Context
- Routes: POST /spaces/{slug}/messages → send, PUT /messages/{id} → edit, DELETE /messages/{id} → delete, GET /spaces/{slug}/messages → history (JSON)
- Send: validate body (required, max 4000 chars), create Message with space_id and user_id, save via repository (plugins intercept here)
- Edit: find message, check ownership (user_id matches or admin), update body, set edited_at
- Delete: find message, check ownership or admin, delete
- History: cursor-paginated JSON response (implemented fully in task 013)
- AuthMiddleware on all routes

## Requirements (Test Descriptions)
- [ ] `it sends a new message on POST /spaces/{slug}/messages`
- [ ] `it validates message body is required and max 4000 characters`
- [ ] `it edits a message on PUT /messages/{id} by the message author`
- [ ] `it rejects editing a message by a non-author non-admin`
- [ ] `it deletes a message on DELETE /messages/{id} by the message author`
- [ ] `it returns message history as JSON on GET /spaces/{slug}/messages`

## Acceptance Criteria
- MessageController at `app/message/src/Controller/MessageController.php`
- All routes use correct HTTP method attributes
- Send returns redirect back to space on success
- Edit/delete check user ownership before proceeding
- Validation uses Marko Validator with named parameters
- Body length limit from config/markotalk.php max_message_length

## Implementation Notes
