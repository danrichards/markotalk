<?php

declare(strict_types=1);

use Marko\Database\Connection\ConnectionInterface;
use Marko\Database\Migration\Migration;

return new class extends Migration {
    public function up(
        ConnectionInterface $connection,
    ): void {
        $this->execute($connection, <<<'SQL'
            CREATE TABLE `notifications` (
                `id` VARCHAR(36) NOT NULL,
                `type` VARCHAR(255) NOT NULL,
                `notifiable_type` VARCHAR(255) NOT NULL,
                `notifiable_id` VARCHAR(255) NOT NULL,
                `data` TEXT NOT NULL,
                `read_at` TIMESTAMP NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                INDEX `idx_notifiable` (`notifiable_type`, `notifiable_id`),
                INDEX `idx_read_at` (`read_at`)
            );
            SQL);
    }

    public function down(
        ConnectionInterface $connection,
    ): void {
        $this->execute($connection, <<<'SQL'
            DROP TABLE `notifications`;
            SQL);
    }
};
