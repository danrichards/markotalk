<?php

declare(strict_types=1);

use Marko\Database\Connection\ConnectionInterface;
use Marko\Database\Migration\Migration;

return new class extends Migration {
    public function up(
        ConnectionInterface $connection,
    ): void {
        $this->execute($connection, <<<'SQL'
            CREATE TABLE "space_memberships" (
                "id" SERIAL PRIMARY KEY,
                "user_id" INTEGER NOT NULL,
                "space_id" INTEGER NOT NULL,
                "last_read_message_id" INTEGER,
                "joined_at" TIMESTAMP NOT NULL
            );
            SQL);

        $this->execute($connection, <<<'SQL'
            CREATE UNIQUE INDEX "idx_space_memberships_user_space" ON "space_memberships" ("user_id", "space_id");
            SQL);

        $this->execute($connection, <<<'SQL'
            ALTER TABLE "space_memberships" ADD CONSTRAINT "fk_space_memberships_user_id" FOREIGN KEY ("user_id") REFERENCES "users" ("id");
            SQL);

        $this->execute($connection, <<<'SQL'
            ALTER TABLE "space_memberships" ADD CONSTRAINT "fk_space_memberships_space_id" FOREIGN KEY ("space_id") REFERENCES "spaces" ("id");
            SQL);

        $this->execute($connection, <<<'SQL'
            ALTER TABLE "space_memberships" ADD CONSTRAINT "fk_space_memberships_last_read_message_id" FOREIGN KEY ("last_read_message_id") REFERENCES "messages" ("id");
            SQL);
    }

    public function down(
        ConnectionInterface $connection,
    ): void {
        $this->execute($connection, <<<'SQL'
            DROP TABLE "space_memberships";
            SQL);
    }
};
