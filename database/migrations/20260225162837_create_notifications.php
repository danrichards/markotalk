<?php

declare(strict_types=1);

use Marko\Database\Connection\ConnectionInterface;
use Marko\Database\Migration\Migration;

return new class extends Migration {
    public function up(
        ConnectionInterface $connection,
    ): void {
        $this->execute($connection, <<<'SQL'
            CREATE TABLE "notifications" (
                "id" VARCHAR(36) PRIMARY KEY,
                "type" VARCHAR(255) NOT NULL,
                "notifiableType" VARCHAR(255) NOT NULL,
                "notifiableId" VARCHAR(255) NOT NULL,
                "data" TEXT NOT NULL,
                "readAt" TIMESTAMP,
                "createdAt" TIMESTAMP NOT NULL
            );
            SQL);
    }

    public function down(
        ConnectionInterface $connection,
    ): void {
        $this->execute($connection, <<<'SQL'
            DROP TABLE "notifications";
            SQL);
    }
};
