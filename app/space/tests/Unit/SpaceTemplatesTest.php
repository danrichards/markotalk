<?php

declare(strict_types=1);

it('renders the space list in the sidebar with all active spaces', function (): void {
    $template = file_get_contents('/Users/markshust/Sites/markotalk/app/space/resources/views/space/index.latte');

    expect($template)
        ->toContain('<nav class="space-list">')
        ->and($template)->toContain('{foreach $spaces as $space}')
        ->and($template)->toContain('href="/spaces/{$space->slug}"')
        ->and($template)->toContain('{$space->name}')
        ->and($template)->toContain('{/foreach}');
});

it('highlights the currently selected space', function (): void {
    $template = file_get_contents('/Users/markshust/Sites/markotalk/app/space/resources/views/space/index.latte');

    expect($template)
        ->toContain('space-item-active')
        ->and($template)->toContain('$currentSpace');
});

it('renders the chat view with space name header', function (): void {
    $template = file_get_contents('/Users/markshust/Sites/markotalk/app/space/resources/views/space/show.latte');

    expect($template)
        ->toContain('{extends "landing::layout"}')
        ->and($template)->toContain('{block content}')
        ->and($template)->toContain('class="chat-area"')
        ->and($template)->toContain('class="chat-header"')
        ->and($template)->toContain('class="chat-title"')
        ->and($template)->toContain('{$space->name}');
});

it('renders the message input form with textarea and send button', function (): void {
    $template = file_get_contents('/Users/markshust/Sites/markotalk/app/space/resources/views/space/show.latte');

    expect($template)
        ->toContain('class="chat-input"')
        ->and($template)->toContain('class="chat-input-form"')
        ->and($template)->toContain('<textarea')
        ->and($template)->toContain('class="chat-input-field"')
        ->and($template)->toContain('class="chat-input-send"')
        ->and($template)->toContain('type="submit"')
        ->and($template)->toContain('name="body"');
});

it('has space.css with all space-* classes defined via @apply', function (): void {
    $css = file_get_contents('/Users/markshust/Sites/markotalk/app/space/src/css/space.css');

    $classes = [
        '.space-list',
        '.space-item',
        '.space-item-active',
        '.space-hash',
        '.space-name',
        '.chat-area',
        '.chat-header',
        '.chat-title',
        '.chat-feed',
        '.chat-input',
        '.chat-input-form',
        '.chat-input-field',
        '.chat-input-send',
    ];

    expect($css)->toContain('@apply');

    foreach ($classes as $class) {
        expect($css)->toContain($class . ' {');
    }
});

it('contains no Tailwind utility classes in any template', function (): void {
    $indexTemplate = file_get_contents('/Users/markshust/Sites/markotalk/app/space/resources/views/space/index.latte');
    $showTemplate = file_get_contents('/Users/markshust/Sites/markotalk/app/space/resources/views/space/show.latte');

    $utilityPatterns = [
        'flex',
        'items-center',
        'text-gray',
        'bg-gray',
        'px-',
        'py-',
        'rounded',
        'font-',
        'text-sm',
        'text-lg',
        'border',
        'overflow',
        'space-y',
    ];

    foreach ($utilityPatterns as $pattern) {
        expect($indexTemplate)->not->toContain('class="' . $pattern)
            ->and($indexTemplate)->not->toContain(' ' . $pattern . ' ')
            ->and($indexTemplate)->not->toContain('"' . $pattern . '"')
            ->and($showTemplate)->not->toContain('class="' . $pattern)
            ->and($showTemplate)->not->toContain(' ' . $pattern . ' ')
            ->and($showTemplate)->not->toContain('"' . $pattern . '"');
    }
});
