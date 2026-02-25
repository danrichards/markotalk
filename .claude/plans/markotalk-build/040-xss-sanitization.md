# Task 040: XSS Sanitization for Markdown Output

**Status**: completed
**Depends on**: 020
**Retry count**: 0

## Description
Add HTML sanitization to the markdown parser output to prevent XSS attacks. Since body_html is rendered with |noescape in templates, the HTML must be safe before storage. Whitelist safe HTML tags and attributes.

## Context
- Markdown parser (task 020) converts body to body_html
- body_html is rendered with `{$message->bodyHtml|noescape}` — Latte won't escape it
- Must sanitize to prevent: script injection, event handler attributes, iframe/object/embed, javascript: URLs
- Whitelist approach: only allow safe tags (p, strong, em, code, pre, a, br, ul, ol, li)
- Only allow safe attributes on a tags (href with http/https only)
- Strip everything else
- Can be part of BasicMarkdownParser or a separate HtmlSanitizer service

## Requirements (Test Descriptions)
- [ ] `it strips script tags from markdown output`
- [ ] `it strips event handler attributes like onclick`
- [ ] `it strips iframe and object tags`
- [ ] `it strips javascript: URLs from links`
- [ ] `it preserves allowed tags (strong, em, code, pre, a, p)`
- [ ] `it preserves href attributes with http/https URLs only`

## Acceptance Criteria
- HtmlSanitizer service or integrated into BasicMarkdownParser
- Whitelisted tags: p, strong, em, code, pre, a, br, ul, ol, li, blockquote
- Whitelisted attributes: href (on a only, http/https only), class (for code highlighting)
- All other tags and attributes stripped
- Applied before body_html is stored in database

## Implementation Notes
