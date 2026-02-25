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
            CREATE TABLE reactions (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                message_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                emoji VARCHAR(50) NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_reactions_message_user_emoji (message_id, user_id, emoji),
                CONSTRAINT fk_reactions_message_id FOREIGN KEY (message_id) REFERENCES messages (id) ON DELETE CASCADE,
                CONSTRAINT fk_reactions_user_id FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            )
            SQL,
        );
    }

    public function down(
        ConnectionInterface $connection,
    ): void {
        $this->execute(
            connection: $connection,
            sql: 'DROP TABLE reactions',
        );
    }
};
