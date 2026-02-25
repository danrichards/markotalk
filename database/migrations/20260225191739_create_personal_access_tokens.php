<?php

declare(strict_types=1);

use Marko\Database\Connection\ConnectionInterface;
use Marko\Database\Migration\Migration;

return new class extends Migration {
    public function up(
        ConnectionInterface $connection,
    ): void {
        $this->execute($connection, <<<'SQL'
            CREATE TABLE "personal_access_tokens" (
                "id" SERIAL PRIMARY KEY,
                "tokenable_type" VARCHAR(255) NOT NULL DEFAULT '',
                "tokenable_id" INTEGER NOT NULL DEFAULT 0,
                "name" VARCHAR(255) NOT NULL DEFAULT '',
                "token_hash" VARCHAR(64) NOT NULL DEFAULT '',
                "abilities" TEXT,
                "last_used_at" VARCHAR(255),
                "expires_at" VARCHAR(255),
                "created_at" VARCHAR(255)
            );
            SQL);
    }

    public function down(
        ConnectionInterface $connection,
    ): void {
        $this->execute($connection, <<<'SQL'
            DROP TABLE "personal_access_tokens";
            SQL);
    }
};
