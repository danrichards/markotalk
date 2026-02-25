<?php

declare(strict_types=1);

use Marko\Database\Connection\ConnectionInterface;
use Marko\Database\Migration\Migration;

return new class extends Migration
{
    public function up(ConnectionInterface $connection): void
    {
        $this->execute(
            connection: $connection,
            sql: <<<SQL
            CREATE TABLE users (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                username VARCHAR(50) NOT NULL,
                email VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                display_name VARCHAR(100) NOT NULL,
                avatar_url VARCHAR(255) NULL,
                role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
                is_banned TINYINT(1) NOT NULL DEFAULT 0,
                last_seen_at DATETIME NULL,
                remember_token VARCHAR(100) NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY idx_users_username (username),
                UNIQUE KEY idx_users_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL,
        );
    }

    public function down(ConnectionInterface $connection): void
    {
        $this->execute(
            connection: $connection,
            sql: 'DROP TABLE IF EXISTS users',
        );
    }
};
