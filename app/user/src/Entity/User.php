<?php

declare(strict_types=1);

namespace App\User\Entity;

use App\User\Enum\UserRole;
use DateTimeImmutable;
use Marko\Authorization\AuthorizableInterface;
use Marko\Database\Attributes\Column;
use Marko\Database\Attributes\Index;
use Marko\Database\Attributes\Table;
use Marko\Database\Entity\Entity;
use Marko\Notification\Contracts\NotifiableInterface;

#[Table(name: 'users')]
#[Index(name: 'idx_users_username', columns: ['username'], unique: true)]
#[Index(name: 'idx_users_email', columns: ['email'], unique: true)]
class User extends Entity implements AuthorizableInterface, NotifiableInterface
{
    public function __construct(
        #[Column(primaryKey: true, autoIncrement: true)]
        public int $id,
        #[Column(name: 'username', length: 50, unique: true)]
        public string $username,
        #[Column(name: 'email', length: 255, unique: true)]
        public string $email,
        #[Column(name: 'password', length: 255)]
        public string $password,
        #[Column(name: 'display_name', length: 100)]
        public string $displayName,
        #[Column(name: 'avatar_url', length: 255)]
        public ?string $avatarUrl,
        #[Column(name: 'role', type: 'enum')]
        public UserRole $role,
        #[Column(name: 'is_banned', type: 'boolean', default: false)]
        public bool $isBanned,
        #[Column(name: 'last_seen_at', type: 'datetime')]
        public ?DateTimeImmutable $lastSeenAt,
        #[Column(name: 'remember_token', length: 100)]
        public ?string $rememberToken,
        #[Column(name: 'created_at', type: 'datetime')]
        public DateTimeImmutable $createdAt,
        #[Column(name: 'updated_at', type: 'datetime')]
        public DateTimeImmutable $updatedAt,
    ) {}

    public function getAuthIdentifier(): int|string
    {
        return $this->id;
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthPassword(): string
    {
        return $this->password;
    }

    public function getRememberToken(): ?string
    {
        return $this->rememberToken;
    }

    public function setRememberToken(?string $token): void
    {
        $this->rememberToken = $token;
    }

    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }

    public function can(string $ability, mixed ...$arguments): bool
    {
        return false;
    }

    public function routeNotificationFor(string $channel): mixed
    {
        return match ($channel) {
            'mail' => $this->email,
            default => ['type' => $this->getNotifiableType(), 'id' => $this->getNotifiableId()],
        };
    }

    public function getNotifiableId(): string|int
    {
        return $this->id;
    }

    public function getNotifiableType(): string
    {
        return self::class;
    }
}
