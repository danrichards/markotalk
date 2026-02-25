<?php

declare(strict_types=1);

namespace App\Message\Service;

readonly class BasicMarkdownParser implements MarkdownParserInterface
{
    public function __construct(
        private HtmlSanitizerInterface $sanitizer = new HtmlSanitizer(),
    ) {}

    public function parse(
        string $markdown,
    ): string {
        // Code blocks: ```...``` -> <pre><code>...</code></pre>
        $result = preg_replace(
            pattern: '/```(.*?)```/s',
            replacement: '<pre><code>$1</code></pre>',
            subject: $markdown,
        );

        // Bold: **text** -> <strong>text</strong>
        $result = preg_replace(
            pattern: '/\*\*(.+?)\*\*/',
            replacement: '<strong>$1</strong>',
            subject: (string) $result,
        );

        // Italic: *text* -> <em>text</em>
        $result = preg_replace(
            pattern: '/\*(.+?)\*/',
            replacement: '<em>$1</em>',
            subject: (string) $result,
        );

        // Inline code: `code` -> <code>code</code>
        $result = preg_replace(
            pattern: '/`([^`]+)`/',
            replacement: '<code>$1</code>',
            subject: (string) $result,
        );

        // Links: [text](url) -> <a href="url">text</a>
        $result = preg_replace(
            pattern: '/\[([^\]]+)\]\(([^)]+)\)/',
            replacement: '<a href="$2">$1</a>',
            subject: (string) $result,
        );

        return $this->sanitizer->sanitize(html: (string) $result);
    }
}
