<?php

declare(strict_types=1);

namespace App\Message\Service;

class HtmlSanitizer implements HtmlSanitizerInterface
{
    public function sanitize(
        string $html,
    ): string {
        // Remove dangerous tags along with their content
        $html = preg_replace(
            pattern: '/<(script|style|object|embed|applet|form|input|button|textarea|select|option)[^>]*>.*?<\/\1>/si',
            replacement: '',
            subject: $html,
        ) ?? $html;

        $html = strip_tags(
            string: $html,
            allowed_tags: '<p><strong><em><code><pre><a><br><ul><ol><li><blockquote>',
        );

        // Remove event handler attributes (onclick, onmouseover, etc.)
        $html = preg_replace(
            pattern: '/\s+on\w+\s*=\s*(?:"[^"]*"|\'[^\']*\')/i',
            replacement: '',
            subject: $html,
        ) ?? $html;

        // Strip javascript: from href attributes
        $html = preg_replace(
            pattern: '/href\s*=\s*"javascript:[^"]*"/i',
            replacement: 'href="#"',
            subject: $html,
        ) ?? $html;

        // Only allow http/https in href — replace any non-http/https href with #
        $html = preg_replace(
            pattern: '/href\s*=\s*"(?!https?:\/\/)[^"]*"/i',
            replacement: 'href="#"',
            subject: $html,
        ) ?? $html;

        return $html;
    }
}
