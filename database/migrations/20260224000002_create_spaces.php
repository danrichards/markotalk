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
            CREATE TABLE spaces (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) NOT NULL,
                description TEXT NULL,
                is_archived TINYINT(1) NOT NULL DEFAULT 0,
                created_by INT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE INDEX idx_spaces_name (name),
                UNIQUE INDEX idx_spaces_slug (slug),
                CONSTRAINT fk_spaces_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE CASCADE
            )
            SQL,
        );
    }

    public function down(
        ConnectionInterface $connection,
    ): void {
        $this->execute(
            connection: $connection,
            sql: 'DROP TABLE spaces',
        );
    }
};
