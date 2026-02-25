<?php

declare(strict_types=1);

use App\Message\Entity\Message;
use App\Message\Plugin\MarkdownPlugin;
use App\Message\Repository\MessageRepository;
use App\Message\Service\BasicMarkdownParser;
use Marko\Core\Attributes\Before;
use Marko\Core\Attributes\Plugin;

it('parses **bold** to <strong>bold</strong>', function (): void {
    $parser = new BasicMarkdownParser();

    expect($parser->parse(markdown: '**bold**'))->toBe('<strong>bold</strong>');
});

it('parses *italic* to <em>italic</em>', function (): void {
    $parser = new BasicMarkdownParser();

    expect($parser->parse(markdown: '*italic*'))->toBe('<em>italic</em>');
});

it('parses inline code with backticks', function (): void {
    $parser = new BasicMarkdownParser();

    expect($parser->parse(markdown: '`code`'))->toBe('<code>code</code>');
});

it('parses code blocks with triple backticks', function (): void {
    $parser = new BasicMarkdownParser();

    expect($parser->parse(markdown: "```\necho 'hello';\n```"))->toBe("<pre><code>\necho 'hello';\n</code></pre>");
});

it('parses [text](url) to anchor tags', function (): void {
    $parser = new BasicMarkdownParser();

    expect($parser->parse(markdown: '[click here](https://example.com)'))->toBe('<a href="https://example.com">click here</a>');
});

it('sets body_html on the message before save via plugin interception', function (): void {
    $parser = new BasicMarkdownParser();
    $plugin = new MarkdownPlugin(parser: $parser);

    $message = new Message(
        id: null,
        spaceId: 1,
        userId: 2,
        body: '**hello**',
        bodyHtml: '',
        isPinned: false,
        editedAt: null,
        createdAt: new DateTimeImmutable('2026-02-24 00:00:00'),
    );

    $result = $plugin->beforeSave(message: $message);

    expect($message->bodyHtml)->toBe('<strong>hello</strong>')
        ->and($result)->toBeNull();
});

it('uses #[Before(sortOrder: 10)] with beforeSave method naming', function (): void {
    $classReflection = new ReflectionClass(MarkdownPlugin::class);
    $classAttributes = $classReflection->getAttributes(Plugin::class);
    $methodReflection = $classReflection->getMethod('beforeSave');
    $methodAttributes = $methodReflection->getAttributes(Before::class);

    expect($classAttributes)->toHaveCount(1)
        ->and($classAttributes[0]->newInstance()->target)->toBe(MessageRepository::class)
        ->and($methodAttributes)->toHaveCount(1)
        ->and($methodAttributes[0]->newInstance()->sortOrder)->toBe(10);
});
