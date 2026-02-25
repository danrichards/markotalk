<?php

declare(strict_types=1);

$appJsPath = __DIR__ . '/../../public/js/app.js';

it('establishes an EventSource connection to the space stream URL', function () use ($appJsPath): void {
    expect(file_exists($appJsPath))->toBeTrue();

    $content = file_get_contents($appJsPath);

    expect($content)->toContain('new EventSource(')
        ->and($content)->toContain('/spaces/')
        ->and($content)->toContain('/stream')
        ->and($content)->toContain('spaceSlug');
});

it('appends received message HTML to the chat feed', function () use ($appJsPath): void {
    $content = file_get_contents($appJsPath);

    expect($content)->toContain("addEventListener('message'")
        ->and($content)->toContain('insertAdjacentHTML')
        ->and($content)->toContain("'beforeend'")
        ->and($content)->toContain('data-message-id')
        ->and($content)->toContain('messageExists');
});

it('auto-scrolls to bottom on new messages when at bottom', function () use ($appJsPath): void {
    $content = file_get_contents($appJsPath);

    expect($content)->toContain('isNearBottom()')
        ->and($content)->toContain('scrollToBottom()')
        ->and($content)->toContain('scrollHeight')
        ->and($content)->toContain('scrollTop');
});

it('does not auto-scroll when user has scrolled up', function () use ($appJsPath): void {
    $content = file_get_contents($appJsPath);

    expect($content)->toContain('userScrolledUp')
        ->and($content)->toContain('const atBottom = isNearBottom()')
        ->and($content)->toContain('if (atBottom)');
});

it('loads older messages on scroll to top via cursor pagination', function () use ($appJsPath): void {
    $content = file_get_contents($appJsPath);

    expect($content)->toContain("addEventListener('scroll'")
        ->and($content)->toContain('nextCursor')
        ->and($content)->toContain('isLoadingOlder')
        ->and($content)->toContain('scrollTop === 0')
        ->and($content)->toContain('/messages?cursor=')
        ->and($content)->toContain('chatFeed.prepend')
        ->and($content)->toContain('prevHeight');
});

it('sends messages via fetch POST without page reload', function () use ($appJsPath): void {
    $content = file_get_contents($appJsPath);

    expect($content)->toContain('.chat-input-form')
        ->and($content)->toContain("addEventListener('submit'")
        ->and($content)->toContain('e.preventDefault()')
        ->and($content)->toContain('new FormData(')
        ->and($content)->toContain("method: 'POST'")
        ->and($content)->toContain("messageInput.value = ''")
        ->and($content)->toContain('messageInput.focus()');
});
