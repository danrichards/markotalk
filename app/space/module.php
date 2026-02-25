<?php

declare(strict_types=1);

use App\Space\Repository\SpaceMembershipRepository;
use App\Space\Repository\SpaceMembershipRepositoryInterface;
use App\Space\Repository\SpaceRepository;
use App\Space\Repository\SpaceRepositoryInterface;

return [
    'bindings' => [
        SpaceRepositoryInterface::class => SpaceRepository::class,
        SpaceMembershipRepositoryInterface::class => SpaceMembershipRepository::class,
    ],
];
