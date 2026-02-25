<?php

declare(strict_types=1);

use App\User\Entity\User;
use App\User\Enum\UserRole;
use App\User\Policy\UserPolicy;

function makeUserPolicyTestUser(int $id = 1, UserRole $role = UserRole::User): User
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

it('allows admin to ban users', function (): void {
    $policy = new UserPolicy();
    $admin = makeUserPolicyTestUser(role: UserRole::Admin);
    $targetUser = makeUserPolicyTestUser(id: 2);

    expect($policy->ban(user: $admin, targetUser: $targetUser))->toBeTrue();
});

it('denies regular users from banning users', function (): void {
    $policy = new UserPolicy();
    $user = makeUserPolicyTestUser();
    $targetUser = makeUserPolicyTestUser(id: 2);

    expect($policy->ban(user: $user, targetUser: $targetUser))->toBeFalse();
});
