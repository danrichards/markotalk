<?php

declare(strict_types=1);

use Marko\Database\Connection\ConnectionInterface;
use Marko\Database\Migration\Migration;

return new class () extends Migration
{
    public function up(
        ConnectionInterface $connection,
    ): void {
        $this->execute(
            connection: $connection,
            sql: <<<'SQL'
            CREATE TABLE users (
                id SERIAL PRIMARY KEY,
                username VARCHAR(50) NOT NULL,
                email VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                display_name VARCHAR(100) NOT NULL,
                avatar_url VARCHAR(255),
                role VARCHAR(20) NOT NULL DEFAULT 'user',
                is_banned BOOLEAN NOT NULL DEFAULT FALSE,
                last_seen_at TIMESTAMP,
                remember_token VARCHAR(100),
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
            SQL,
        );

        $this->execute(
            connection: $connection,
            sql: 'CREATE UNIQUE INDEX idx_users_username ON users (username)',
        );

        $this->execute(
            connection: $connection,
            sql: 'CREATE UNIQUE INDEX idx_users_email ON users (email)',
        );
    }

    public function down(
        ConnectionInterface $connection,
    ): void {
        $this->execute(
            connection: $connection,
            sql: 'DROP TABLE users',
        );
    }
};
