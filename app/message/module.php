<?php

declare(strict_types=1);

use App\Message\Repository\MessageRepository;
use App\Message\Repository\MessageRepositoryInterface;
use App\Message\Repository\ReactionRepository;
use App\Message\Repository\ReactionRepositoryInterface;
use App\Message\Service\BasicMarkdownParser;
use App\Message\Service\MarkdownParserInterface;

return [
    'bindings' => [
        MessageRepositoryInterface::class => MessageRepository::class,
        ReactionRepositoryInterface::class => ReactionRepository::class,
        MarkdownParserInterface::class => BasicMarkdownParser::class,
    ],
];
