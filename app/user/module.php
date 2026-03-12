<?php

declare(strict_types=1);

use App\User\Provider\DatabaseUserProvider;
use App\User\Repository\UserRepository;
use App\User\Repository\UserRepositoryInterface;
use App\User\Service\DatabasePresenceTracker;
use App\User\Service\PresenceTrackerInterface;
use Marko\Authentication\Contracts\UserProviderInterface;

return [
    'bindings' => [
        UserRepositoryInterface::class => UserRepository::class,
        UserProviderInterface::class => DatabaseUserProvider::class,
        PresenceTrackerInterface::class => DatabasePresenceTracker::class,
    ],
];
