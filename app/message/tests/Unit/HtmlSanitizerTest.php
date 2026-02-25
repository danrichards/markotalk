<?php

declare(strict_types=1);

use App\Message\Service\HtmlSanitizer;

it('strips script tags from markdown output', function (): void {
    $sanitizer = new HtmlSanitizer();

    expect($sanitizer->sanitize(html: '<p>Hello</p><script>alert("xss")</script>'))->toBe('<p>Hello</p>');
});

it('strips event handler attributes like onclick', function (): void {
    $sanitizer = new HtmlSanitizer();

    expect($sanitizer->sanitize(html: '<p onclick="alert(1)">Hello</p>'))->toBe('<p>Hello</p>');
});

it('strips iframe and object tags', function (): void {
    $sanitizer = new HtmlSanitizer();

    expect($sanitizer->sanitize(html: '<p>Hello</p><iframe src="evil.com"></iframe><object data="evil.swf"></object>'))
        ->toBe('<p>Hello</p>');
});

it('strips javascript: URLs from links', function (): void {
    $sanitizer = new HtmlSanitizer();

    expect($sanitizer->sanitize(html: '<a href="javascript:alert(1)">click me</a>'))
        ->toBe('<a href="#">click me</a>');
});

it('preserves allowed tags (strong, em, code, pre, a, p)', function (): void {
    $sanitizer = new HtmlSanitizer();

    $html = '<p>Hello <strong>bold</strong> and <em>italic</em> with <code>code</code></p><pre><code>block</code></pre><a href="https://example.com">link</a>';

    expect($sanitizer->sanitize(html: $html))->toBe($html);
});

it('preserves href attributes with http/https URLs only', function (): void {
    $sanitizer = new HtmlSanitizer();

    expect($sanitizer->sanitize(html: '<a href="https://example.com">secure</a>'))
        ->toBe('<a href="https://example.com">secure</a>')
        ->and($sanitizer->sanitize(html: '<a href="http://example.com">http link</a>'))
        ->toBe('<a href="http://example.com">http link</a>')
        ->and($sanitizer->sanitize(html: '<a href="ftp://evil.com">ftp link</a>'))
        ->toBe('<a href="#">ftp link</a>');
});
