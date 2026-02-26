<?php

declare(strict_types=1);

$appJsPath = __DIR__ . '/../../public/js/app.js';

it('establishes an EventSource connection to the space stream URL', function () use ($appJsPath): void {
    expect(file_exists(filename: $appJsPath))->toBeTrue();

    $content = file_get_contents(filename: $appJsPath);

    expect($content)->toContain('new EventSource(')
        ->and($content)->toContain('/spaces/')
        ->and($content)->toContain('/stream')
        ->and($content)->toContain('spaceSlug');
});

it('listens for space:{slug} events instead of separate message, message_edited, and presence events', function () use ($appJsPath): void {
    $content = file_get_contents(filename: $appJsPath);

    expect($content)->toContain("addEventListener('space:' + spaceSlug")
        ->and($content)->not->toContain("addEventListener('message_edited'")
        ->and($content)->not->toContain("addEventListener('presence'");
});

it('parses the event data as JSON and routes by type field', function () use ($appJsPath): void {
    $content = file_get_contents(filename: $appJsPath);

    expect($content)->toContain('JSON.parse(e.data)')
        ->and($content)->toContain('switch (event.type)')
        ->and($content)->toContain("case 'message'")
        ->and($content)->toContain("case 'message_edited'")
        ->and($content)->toContain("case 'message_deleted'")
        ->and($content)->toContain("case 'presence'");
});

it('appends message HTML for type message events', function () use ($appJsPath): void {
    $content = file_get_contents(filename: $appJsPath);

    expect($content)->toContain("case 'message'")
        ->and($content)->toContain('event.html')
        ->and($content)->toContain('appendMessage(event.html)')
        ->and($content)->toContain('msgEl.dataset.messageId')
        ->and($content)->toContain('messageExists(msgId)');
});

it('appends received message HTML to the chat feed', function () use ($appJsPath): void {
    $content = file_get_contents(filename: $appJsPath);

    expect($content)->toContain('insertAdjacentHTML')
        ->and($content)->toContain("'beforeend'")
        ->and($content)->toContain('data-message-id')
        ->and($content)->toContain('messageExists');
});

it('auto-scrolls to bottom on new messages when at bottom', function () use ($appJsPath): void {
    $content = file_get_contents(filename: $appJsPath);

    expect($content)->toContain('isNearBottom()')
        ->and($content)->toContain('scrollToBottom()')
        ->and($content)->toContain('scrollHeight')
        ->and($content)->toContain('scrollTop');
});

it('does not auto-scroll when user has scrolled up', function () use ($appJsPath): void {
    $content = file_get_contents(filename: $appJsPath);

    expect($content)->toContain('userScrolledUp')
        ->and($content)->toContain('const atBottom = isNearBottom()')
        ->and($content)->toContain('if (atBottom)');
});

it('loads older messages on scroll to top via cursor pagination', function () use ($appJsPath): void {
    $content = file_get_contents(filename: $appJsPath);

    expect($content)->toContain("addEventListener('scroll'")
        ->and($content)->toContain('nextCursor')
        ->and($content)->toContain('isLoadingOlder')
        ->and($content)->toContain('scrollTop === 0')
        ->and($content)->toContain('/messages?cursor=')
        ->and($content)->toContain('chatFeed.prepend')
        ->and($content)->toContain('prevHeight');
});

it('adds action buttons for the current user\'s own messages', function () use ($appJsPath): void {
    $content = file_get_contents(filename: $appJsPath);

    expect($content)->toContain('currentUserId')
        ->and($content)->toContain('event.userId')
        ->and($content)->toContain('chatFeed.dataset.currentUserId')
        ->and($content)->toContain('message-actions')
        ->and($content)->toContain('message-action-edit')
        ->and($content)->toContain('message-action-delete')
        ->and($content)->toContain('insertAdjacentHTML');
});

it('has a data-current-user-id attribute on the chat feed element', function (): void {
    $template = file_get_contents(filename: '/Users/markshust/Sites/markotalk/app/space/resources/views/space/show.latte');

    expect($template)->toContain('data-current-user-id="{$currentUser->id}"');
});

it('updates member indicators for type presence events', function () use ($appJsPath): void {
    $content = file_get_contents(filename: $appJsPath);

    expect($content)->toContain("case 'presence'")
        ->and($content)->toContain('event.onlineIds')
        ->and($content)->toContain('member-item')
        ->and($content)->toContain('member-indicator')
        ->and($content)->toContain('is-online')
        ->and($content)->toContain('is-offline');
});

it('removes message elements for type message_deleted events', function () use ($appJsPath): void {
    $content = file_get_contents(filename: $appJsPath);

    expect($content)->toContain("case 'message_deleted'")
        ->and($content)->toContain('msgEl.remove()');
});

it('sends messages via fetch POST without page reload', function () use ($appJsPath): void {
    $content = file_get_contents(filename: $appJsPath);

    expect($content)->toContain('.chat-input-form')
        ->and($content)->toContain("addEventListener('submit'")
        ->and($content)->toContain('e.preventDefault()')
        ->and($content)->toContain('new FormData(')
        ->and($content)->toContain("method: 'POST'")
        ->and($content)->toContain("messageInput.value = ''")
        ->and($content)->toContain('messageInput.focus()');
});
