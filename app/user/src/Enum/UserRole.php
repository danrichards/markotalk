<?php

declare(strict_types=1);

namespace App\User\Enum;

enum UserRole: string
{
    case User = 'user';
    case Admin = 'admin';
}
