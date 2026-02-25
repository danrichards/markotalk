<?php

declare(strict_types=1);

namespace App\Message\Service;

interface HtmlSanitizerInterface
{
    public function sanitize(
        string $html,
    ): string;
}
