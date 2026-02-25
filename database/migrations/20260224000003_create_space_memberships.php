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
            CREATE TABLE space_memberships (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT UNSIGNED NOT NULL,
                space_id INT UNSIGNED NOT NULL,
                last_read_message_id INT UNSIGNED NULL,
                joined_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE INDEX idx_space_memberships_user_space (user_id, space_id),
                CONSTRAINT fk_space_memberships_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
                CONSTRAINT fk_space_memberships_space FOREIGN KEY (space_id) REFERENCES spaces (id) ON DELETE CASCADE,
                CONSTRAINT fk_space_memberships_message FOREIGN KEY (last_read_message_id) REFERENCES messages (id) ON DELETE SET NULL
            )
            SQL,
        );
    }

    public function down(
        ConnectionInterface $connection,
    ): void {
        $this->execute(
            connection: $connection,
            sql: 'DROP TABLE space_memberships',
        );
    }
};
