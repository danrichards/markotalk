<?php

declare(strict_types=1);

namespace App\Message\Service;

interface MarkdownParserInterface
{
    public function parse(
        string $markdown,
    ): string;
}
