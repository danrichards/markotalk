<?php

declare(strict_types=1);

namespace App\Message\Plugin;

use App\Message\Entity\Message;
use App\Message\Repository\MessageRepository;
use App\Message\Service\MarkdownParserInterface;
use Marko\Core\Attributes\Before;
use Marko\Core\Attributes\Plugin;

#[Plugin(target: MessageRepository::class)]
readonly class MarkdownPlugin
{
    public function __construct(
        private MarkdownParserInterface $parser,
    ) {}

    #[Before(sortOrder: 10)]
    public function beforeSave(
        Message $message,
    ): null {
        $message->bodyHtml = $this->parser->parse(markdown: $message->body);

        return null;
    }
}
