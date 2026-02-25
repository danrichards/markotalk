<?php

declare(strict_types=1);

use App\Message\Entity\Message;
use App\Message\Policy\MessagePolicy;
use App\User\Entity\User;
use App\User\Enum\UserRole;

function makePolicyUser(int $id = 1, UserRole $role = UserRole::User): User
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
        createdAt: new DateTimeImmutable('2026-02-24 00:00:00'),
        updatedAt: new DateTimeImmutable('2026-02-24 00:00:00'),
    );
}

function makePolicyMessage(int $userId = 1): Message
{
    return new Message(
        id: 1,
        spaceId: 1,
        userId: $userId,
        body: 'Hello world',
        bodyHtml: '<p>Hello world</p>',
        isPinned: false,
        editedAt: null,
        createdAt: new DateTimeImmutable('2026-02-24 00:00:00'),
    );
}

it('allows message author to edit their own message', function (): void {
    $policy = new MessagePolicy();
    $user = makePolicyUser(id: 1);
    $message = makePolicyMessage(userId: 1);

    expect($policy->edit(user: $user, message: $message))->toBeTrue();
});

it('allows admin to edit any message', function (): void {
    $policy = new MessagePolicy();
    $admin = makePolicyUser(id: 2, role: UserRole::Admin);
    $message = makePolicyMessage(userId: 1);

    expect($policy->edit(user: $admin, message: $message))->toBeTrue();
});

it('denies non-author non-admin from editing a message', function (): void {
    $policy = new MessagePolicy();
    $user = makePolicyUser(id: 3);
    $message = makePolicyMessage(userId: 1);

    expect($policy->edit(user: $user, message: $message))->toBeFalse();
});
