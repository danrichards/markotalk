<?php

declare(strict_types=1);

return [
    'processes' => [
        'tailwind' => 'npx @tailwindcss/cli -i src/css/app.css -o public/css/app.css --watch',
    ],
];
