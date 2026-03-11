<?php

declare(strict_types=1);

it('renders a message with author name and body', function (): void {
    $templateFile = dirname(path: __DIR__, levels: 2) . '/resources/views/_message.latte';

    expect(file_exists(filename: $templateFile))->toBeTrue();

    $content = file_get_contents(filename: $templateFile);

    expect($content)
        ->toContain('data-message-id="{$message->id}"')
        ->toContain('{$userMap[$message->userId]')
        ->toContain('{$message->bodyHtml|noescape}');
});

it('renders body_html with noescape filter', function (): void {
    $templateFile = dirname(path: __DIR__, levels: 2) . '/resources/views/_message.latte';

    expect(file_exists(filename: $templateFile))->toBeTrue();

    $content = file_get_contents(filename: $templateFile);

    expect($content)->toContain('|noescape');
});

it('shows a pinned badge for pinned messages', function (): void {
    $templateFile = dirname(path: __DIR__, levels: 2) . '/resources/views/_message.latte';

    expect(file_exists(filename: $templateFile))->toBeTrue();

    $content = file_get_contents(filename: $templateFile);

    expect($content)
        ->toContain('$message->isPinned')
        ->toContain('message-pin-badge')
        ->toContain('Pinned');
});

it('shows edit and delete actions for the message author', function (): void {
    $templateFile = dirname(path: __DIR__, levels: 2) . '/resources/views/_message.latte';

    expect(file_exists(filename: $templateFile))->toBeTrue();

    $content = file_get_contents(filename: $templateFile);

    expect($content)
        ->toContain('$currentUser->id === $message->userId')
        ->toContain('message-action-edit')
        ->toContain('message-action-delete');
});

it('shows a pin button for admin users', function (): void {
    $templateFile = dirname(path: __DIR__, levels: 2) . '/resources/views/_message.latte';

    expect(file_exists(filename: $templateFile))->toBeTrue();

    $content = file_get_contents(filename: $templateFile);

    expect($content)
        ->toContain('message-action-pin')
        ->toContain('message-action-pin-form')
        ->toContain('/messages/{$message->id}/pin')
        ->toContain('UserRole::Admin');
});

it('has message.css with all message-* classes defined via @apply', function (): void {
    $cssFile = dirname(path: __DIR__, levels: 2) . '/src/css/message.css';

    expect(file_exists(filename: $cssFile))->toBeTrue();

    $content = file_get_contents(filename: $cssFile);

    expect($content)
        ->toContain('.message {')
        ->toContain('.message-pinned {')
        ->toContain('.message-header {')
        ->toContain('.message-avatar {')
        ->toContain('.message-author {')
        ->toContain('.message-timestamp {')
        ->toContain('.message-pin-badge {')
        ->toContain('.message-body {')
        ->toContain('.message-actions {')
        ->toContain('.message-action-edit {')
        ->toContain('.message-action-delete {')
        ->toContain('.message-action-pin-form {')
        ->toContain('.message-action-pin {')
        ->toContain('.message-action-form {')
        ->toContain('@apply');
});

it('contains no Tailwind utility classes in the template', function (): void {
    $templateFile = dirname(path: __DIR__, levels: 2) . '/resources/views/_message.latte';

    expect(file_exists(filename: $templateFile))->toBeTrue();

    $content = file_get_contents(filename: $templateFile);

    // Check that no class attributes contain Tailwind utility classes
    // Tailwind utility patterns like flex, text-*, bg-*, p-*, m-*, etc.
    preg_match_all(pattern: '/class="([^"]+)"/', subject: $content, matches: $matches);

    $tailwindPattern = '/\b(flex|grid|block|inline|hidden|text-(?:xs|sm|base|lg|xl|2xl|gray|white|black|red|blue|green|yellow|brand)|bg-(?:white|gray|red|blue|green|yellow|brand)|p-\d|px-\d|py-\d|m-\d|mx-\d|my-\d|mt-\d|mb-\d|ml-\d|mr-\d|w-\d|h-\d|rounded|border|font-(?:bold|semibold|medium)|hover:|focus:|gap-\d|items-|justify-|space-|overflow-|cursor-|transition|duration-|ease-|opacity-|shadow|z-)\b/';

    foreach ($matches[1] as $classValue) {
        // Each class in the template should be a semantic class name (message-*), not a Tailwind utility
        $classes = explode(separator: ' ', string: trim(string: $classValue));
        foreach ($classes as $class) {
            if (str_starts_with(haystack: $class, needle: '{')) {
                continue; // Skip Latte expressions
            }
            expect(preg_match(pattern: $tailwindPattern, subject: $class))->toBe(0, "Found Tailwind utility class: {$class}");
        }
    }
});
