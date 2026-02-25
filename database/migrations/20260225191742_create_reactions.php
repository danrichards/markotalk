<?php

declare(strict_types=1);

use Marko\Database\Connection\ConnectionInterface;
use Marko\Database\Migration\Migration;

return new class extends Migration {
    public function up(
        ConnectionInterface $connection,
    ): void {
        $this->execute($connection, <<<'SQL'
            CREATE TABLE "reactions" (
                "id" SERIAL PRIMARY KEY,
                "message_id" INTEGER NOT NULL,
                "user_id" INTEGER NOT NULL,
                "emoji" VARCHAR(50) NOT NULL
            );
            SQL);

        $this->execute($connection, <<<'SQL'
            CREATE UNIQUE INDEX "uniq_reactions_message_user_emoji" ON "reactions" ("message_id", "user_id", "emoji");
            SQL);

        $this->execute($connection, <<<'SQL'
            ALTER TABLE "reactions" ADD CONSTRAINT "fk_reactions_message_id" FOREIGN KEY ("message_id") REFERENCES "messages" ("id");
            SQL);

        $this->execute($connection, <<<'SQL'
            ALTER TABLE "reactions" ADD CONSTRAINT "fk_reactions_user_id" FOREIGN KEY ("user_id") REFERENCES "users" ("id");
            SQL);
    }

    public function down(
        ConnectionInterface $connection,
    ): void {
        $this->execute($connection, <<<'SQL'
            DROP TABLE "reactions";
            SQL);
    }
};
