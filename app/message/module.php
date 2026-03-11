<?php

declare(strict_types=1);

use App\Message\Entity\Message;
use App\Message\Policy\MessagePolicy;
use App\Message\Repository\MessageRepository;
use App\Message\Repository\MessageRepositoryInterface;
use App\Message\Repository\ReactionRepository;
use App\Message\Repository\ReactionRepositoryInterface;
use App\Message\Service\BasicMarkdownParser;
use App\Message\Service\MarkdownParserInterface;
use Marko\Authorization\Contracts\GateInterface;

return [
    'bindings' => [
        MessageRepositoryInterface::class => MessageRepository::class,
        ReactionRepositoryInterface::class => ReactionRepository::class,
        MarkdownParserInterface::class => BasicMarkdownParser::class,
    ],
    'boot' => function (GateInterface $gate): void {
        $gate->policy(Message::class, MessagePolicy::class);
    },
];
