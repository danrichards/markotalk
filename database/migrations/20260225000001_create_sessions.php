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
            CREATE TABLE sessions (
                id VARCHAR(255) NOT NULL,
                payload TEXT NOT NULL,
                last_activity INTEGER NOT NULL,
                PRIMARY KEY (id)
            )
            SQL,
        );

        $this->execute(
            connection: $connection,
            sql: 'CREATE INDEX idx_sessions_last_activity ON sessions (last_activity)',
        );
    }

    public function down(
        ConnectionInterface $connection,
    ): void {
        $this->execute(
            connection: $connection,
            sql: 'DROP TABLE sessions',
        );
    }
};
