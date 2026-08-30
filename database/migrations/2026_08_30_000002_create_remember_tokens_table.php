<?php

declare(strict_types=1);

use Database\Migration\Migration;

/**
 * Tokens « Se souvenir de moi » (Astral AuthExtra).
 */
class CreateRememberTokensTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec(<<<'SQL'
                CREATE TABLE IF NOT EXISTS remember_tokens (
                    id         INTEGER PRIMARY KEY,
                    selector   TEXT    NOT NULL UNIQUE,
                    token_hash TEXT    NOT NULL,
                    user_id    INTEGER NOT NULL,
                    expires_at TEXT    NOT NULL,
                    created_at TEXT    NOT NULL DEFAULT (datetime('now'))
                )
            SQL);
            $pdo->exec('CREATE INDEX IF NOT EXISTS remember_tokens_user_idx ON remember_tokens (user_id)');
        } else {
            $pdo->exec(<<<'SQL'
                CREATE TABLE IF NOT EXISTS `remember_tokens` (
                    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `selector`   VARCHAR(64)     NOT NULL,
                    `token_hash` VARCHAR(64)     NOT NULL,
                    `user_id`    INT UNSIGNED    NOT NULL,
                    `expires_at` DATETIME        NOT NULL,
                    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `remember_tokens_selector_unique` (`selector`),
                    KEY `remember_tokens_user_idx` (`user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
        }
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS remember_tokens');
    }
}
