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
            CREATE TABLE messages (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                space_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                body TEXT NOT NULL,
                body_html TEXT NOT NULL,
                is_pinned TINYINT(1) NOT NULL DEFAULT 0,
                edited_at DATETIME NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                INDEX idx_messages_space_id (space_id),
                INDEX idx_messages_space_id_id (space_id, id),
                CONSTRAINT fk_messages_space_id FOREIGN KEY (space_id) REFERENCES spaces (id) ON DELETE CASCADE,
                CONSTRAINT fk_messages_user_id FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            )
            SQL,
        );
    }

    public function down(
        ConnectionInterface $connection,
    ): void {
        $this->execute(
            connection: $connection,
            sql: 'DROP TABLE messages',
        );
    }
};
