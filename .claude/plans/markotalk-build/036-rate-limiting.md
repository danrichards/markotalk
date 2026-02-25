# Task 036: Rate Limiting on Message Sends

**Status**: completed
**Depends on**: 012
**Retry count**: 0

## Description
Add rate limiting to message sending to prevent spam. Uses marko/rate-limiting package. Limit: configurable messages per time window (e.g., 10 messages per 30 seconds per user).

## Context
- Apply rate limiting middleware or check in MessageController::send()
- Rate limit key: user:{userId}:messages
- Default: 10 messages per 30 seconds
- Return 429 Too Many Requests with retry-after header when exceeded
- Config in config/markotalk.php: rate_limit_messages, rate_limit_window
- marko/rate-limiting package must be added to composer.json dependencies

## Requirements (Test Descriptions)
- [ ] `it allows messages within the rate limit`
- [ ] `it returns 429 when rate limit is exceeded`
- [ ] `it includes retry-after header in rate limit response`
- [ ] `it limits per user, not globally`
- [ ] `it reads rate limit config from markotalk.php`

## Acceptance Criteria
- Rate limiting applied to POST /spaces/{slug}/messages
- marko/rate-limiting added to root composer.json
- Config values in config/markotalk.php
- Clear error message for users when rate limited
- Admins may be exempt (optional)

## Implementation Notes
