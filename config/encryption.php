<?php

declare(strict_types=1);

return [
    'key' => env('ENCRYPTION_KEY', ''),
    'cipher' => 'aes-256-gcm',
];
