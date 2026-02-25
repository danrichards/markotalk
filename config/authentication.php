<?php

declare(strict_types=1);

return [
    'default' => [
        'guard' => 'session',
        'provider' => 'users',
    ],
    'guards' => [
        'session' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],
    'password' => [
        'driver' => 'bcrypt',
        'bcrypt' => [
            'cost' => 12,
        ],
    ],
    'remember' => [
        'expiration' => 43200,
        'cookie' => 'remember_token',
    ],
];
