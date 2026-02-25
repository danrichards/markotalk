<?php

declare(strict_types=1);

use App\Space\Entity\Space;
use App\Space\Policy\SpacePolicy;
use App\User\Entity\User;
use App\User\Enum\UserRole;

function makeSpaceTestUser(int $id = 1, UserRole $role = UserRole::User): User
{
    return new User(
        id: $id,
        username: 'user' . $id,
        email: 'user' . $id . '@example.com',
        password: 'hashed_password',
        displayName: 'User ' . $id,
        avatarUrl: null,
        role: $role,
        isBanned: false,
        lastSeenAt: null,
        rememberToken: null,
        createdAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
        updatedAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );
}

function makeTestSpace(): Space
{
    return new Space(
        id: 1,
        name: 'General',
        slug: 'general',
        description: null,
        isArchived: false,
        createdBy: 1,
        createdAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
        updatedAt: new DateTimeImmutable(datetime: '2026-02-24 00:00:00'),
    );
}

it('allows admin to create spaces', function (): void {
    $policy = new SpacePolicy();
    $admin = makeSpaceTestUser(role: UserRole::Admin);

    expect($policy->create(user: $admin))->toBeTrue();
});

it('denies regular users from creating spaces', function (): void {
    $policy = new SpacePolicy();
    $user = makeSpaceTestUser();

    expect($policy->create(user: $user))->toBeFalse();
});
