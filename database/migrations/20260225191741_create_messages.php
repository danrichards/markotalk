<?php

declare(strict_types=1);

use Marko\Database\Connection\ConnectionInterface;
use Marko\Database\Migration\Migration;

return new class extends Migration {
    public function up(
        ConnectionInterface $connection,
    ): void {
        $this->execute($connection, <<<'SQL'
            CREATE TABLE "messages" (
                "id" SERIAL PRIMARY KEY,
                "space_id" INTEGER NOT NULL,
                "user_id" INTEGER NOT NULL,
                "body" TEXT NOT NULL,
                "body_html" TEXT NOT NULL,
                "is_pinned" BOOLEAN NOT NULL DEFAULT FALSE,
                "edited_at" TIMESTAMP,
                "created_at" TIMESTAMP NOT NULL
            );
            SQL);

        $this->execute($connection, <<<'SQL'
            CREATE INDEX "idx_messages_space_id_id" ON "messages" ("space_id", "id");
            SQL);

        $this->execute($connection, <<<'SQL'
            ALTER TABLE "messages" ADD CONSTRAINT "fk_messages_space_id" FOREIGN KEY ("space_id") REFERENCES "spaces" ("id");
            SQL);

        $this->execute($connection, <<<'SQL'
            ALTER TABLE "messages" ADD CONSTRAINT "fk_messages_user_id" FOREIGN KEY ("user_id") REFERENCES "users" ("id");
            SQL);
    }

    public function down(
        ConnectionInterface $connection,
    ): void {
        $this->execute($connection, <<<'SQL'
            DROP TABLE "messages";
            SQL);
    }
};
