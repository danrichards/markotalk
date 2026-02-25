<?php

declare(strict_types=1);

use Marko\Database\Connection\ConnectionInterface;
use Marko\Database\Migration\Migration;

return new class extends Migration {
    public function up(
        ConnectionInterface $connection,
    ): void {
        $this->execute($connection, <<<'SQL'
            CREATE TABLE "spaces" (
                "id" SERIAL PRIMARY KEY,
                "name" VARCHAR(100) NOT NULL UNIQUE,
                "slug" VARCHAR(100) NOT NULL UNIQUE,
                "description" TEXT,
                "is_archived" BOOLEAN NOT NULL DEFAULT FALSE,
                "created_by" INTEGER NOT NULL,
                "created_at" TIMESTAMP NOT NULL,
                "updated_at" TIMESTAMP NOT NULL
            );
            SQL);

        $this->execute($connection, <<<'SQL'
            CREATE UNIQUE INDEX "idx_spaces_slug" ON "spaces" ("slug");
            SQL);

        $this->execute($connection, <<<'SQL'
            CREATE UNIQUE INDEX "idx_spaces_name" ON "spaces" ("name");
            SQL);

        $this->execute($connection, <<<'SQL'
            ALTER TABLE "spaces" ADD CONSTRAINT "fk_spaces_created_by" FOREIGN KEY ("created_by") REFERENCES "users" ("id");
            SQL);
    }

    public function down(
        ConnectionInterface $connection,
    ): void {
        $this->execute($connection, <<<'SQL'
            DROP TABLE "spaces";
            SQL);
    }
};
