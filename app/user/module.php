<?php

declare(strict_types=1);

use App\User\Provider\DatabaseUserProvider;
use App\User\Repository\UserRepository;
use App\User\Repository\UserRepositoryInterface;
use App\User\Service\DatabasePresenceTracker;
use App\User\Service\PresenceTrackerInterface;
use Marko\Authentication\AuthManager;
use Marko\Authentication\Contracts\GuardInterface;
use Marko\Authentication\Contracts\PasswordHasherInterface;
use Marko\Authentication\Contracts\UserProviderInterface;
use Marko\Core\Container\ContainerInterface;

return [
    'bindings' => [
        GuardInterface::class => function (ContainerInterface $container): GuardInterface {
            return $container->get(AuthManager::class)->guard();
        },
        UserRepositoryInterface::class => UserRepository::class,
        UserProviderInterface::class => function (ContainerInterface $container): UserProviderInterface {
            return new DatabaseUserProvider(
                userRepository: $container->get(UserRepositoryInterface::class),
                hasher: $container->get(PasswordHasherInterface::class),
            );
        },
        PresenceTrackerInterface::class => function (ContainerInterface $container): PresenceTrackerInterface {
            $config = require __DIR__ . '/../../config/markotalk.php';

            return new DatabasePresenceTracker(
                userRepository: $container->get(UserRepositoryInterface::class),
                presenceTimeout: $config['presence_timeout'],
            );
        },
    ],
];
