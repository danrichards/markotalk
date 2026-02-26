<?php

declare(strict_types=1);

return [
    'max_message_length' => 2000,
    'rate_limit_messages' => 10,
    'rate_limit_window' => 30,
    'presence_timeout' => 30,
    'sse_poll_interval' => 1,
    'sse_heartbeat_interval' => 15,
    'sse_timeout' => 300,
    'default_spaces' => [
        ['name' => 'General', 'slug' => 'general', 'description' => 'General discussion'],
        ['name' => 'Help', 'slug' => 'help', 'description' => 'Get help with Marko'],
        ['name' => 'Showcase', 'slug' => 'showcase', 'description' => 'Show off your projects'],
    ],
];
