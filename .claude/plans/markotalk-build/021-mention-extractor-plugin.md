# Task 021: MentionExtractorPlugin

**Status**: completed
**Depends on**: 011, 019
**Retry count**: 0

## Description
Create MentionExtractorPlugin that intercepts MessageRepository::save() with #[After] to extract @mentions from the message body and dispatch MentionDetectedEvent for each mentioned username.

## Context
- MentionExtractorPlugin uses `#[Plugin(target: MessageRepository::class)]`
- Method: `#[After(sortOrder: 10)]` on `afterSave(mixed $result, Message $message): mixed`
- CRITICAL: method must be named `afterSave` (naming convention)
- CRITICAL: After plugins receive `(mixed $result, ...$originalArgs)` and MUST return result
- Regex pattern: `/@([a-zA-Z0-9_]+)/` to find mentions
- For each unique username found, dispatch MentionDetectedEvent
- Does NOT check if username exists — that's the observer's job

## Requirements (Test Descriptions)
- [ ] `it extracts single @mention from message body`
- [ ] `it extracts multiple @mentions from message body`
- [ ] `it ignores duplicate @mentions in the same message`
- [ ] `it dispatches MentionDetectedEvent for each unique mention`
- [ ] `it returns the original result from afterSave`
- [ ] `it uses #[After(sortOrder: 10)] with afterSave method naming`

## Acceptance Criteria
- MentionExtractorPlugin at `app/message/src/Plugin/MentionExtractorPlugin.php`
- Uses `#[Plugin(target: MessageRepository::class)]` and `#[After(sortOrder: 10)]`
- Method signature: `afterSave(mixed $result, Message $message): mixed`
- Dispatches MentionDetectedEvent(message: $message, username: $username) for each mention
- Returns $result unchanged

## Implementation Notes
