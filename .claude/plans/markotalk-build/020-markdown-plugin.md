# Task 020: Markdown Parser and MarkdownPlugin

**Status**: completed
**Depends on**: 011, 019
**Retry count**: 0

## Description
Create BasicMarkdownParser (swappable via Preference) and MarkdownPlugin that intercepts MessageRepository::save() with #[Before] to parse body into body_html. Demonstrates method interception without the repository knowing about markdown.

## Context
- MarkdownPlugin uses `#[Plugin(target: MessageRepository::class)]`
- Method: `#[Before(sortOrder: 10)]` on `beforeSave(Message $message): null`
- CRITICAL: method must be named `beforeSave` (naming convention), NOT `parseMarkdown`
- CRITICAL: #[Before] has NO `method:` parameter — only `sortOrder`
- Before plugin returns null to pass through (non-null would short-circuit)
- BasicMarkdownParser handles: **bold**, *italic*, `code`, ```code blocks```, [links](url)
- Parser sanitizes HTML output to prevent XSS (task 040 adds thorough sanitization)
- Preference showcase: `#[Preference(replaces: BasicMarkdownParser::class)]` could swap implementation

## Requirements (Test Descriptions)
- [ ] `it parses **bold** to <strong>bold</strong>`
- [ ] `it parses *italic* to <em>italic</em>`
- [ ] `it parses inline code with backticks`
- [ ] `it parses code blocks with triple backticks`
- [ ] `it parses [text](url) to anchor tags`
- [ ] `it sets body_html on the message before save via plugin interception`
- [ ] `it uses #[Before(sortOrder: 10)] with beforeSave method naming`

## Acceptance Criteria
- MarkdownParserInterface at `app/message/src/Service/MarkdownParserInterface.php`
- BasicMarkdownParser at `app/message/src/Service/BasicMarkdownParser.php`
- MarkdownPlugin at `app/message/src/Plugin/MarkdownPlugin.php`
- Plugin uses correct framework conventions: `#[Plugin(target:)]`, `#[Before(sortOrder:)]`, `beforeSave` method name
- Bindings for MarkdownParserInterface in message module.php

## Implementation Notes
