<?php

declare(strict_types=1);

use Database\Migration\Migration;

/**
 * Journal optionnel des tentatives de connexion (Astral AuthExtra).
 */
class CreateLoginAttemptsTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec(<<<'SQL'
                CREATE TABLE IF NOT EXISTS login_attempts (
                    id         INTEGER PRIMARY KEY,
                    email      TEXT    NOT NULL,
                    ip_address TEXT    NOT NULL,
                    user_id    INTEGER NULL,
                    successful INTEGER NOT NULL DEFAULT 0,
                    reason     TEXT    NULL,
                    created_at TEXT    NOT NULL DEFAULT (datetime('now'))
                )
            SQL);
            $pdo->exec('CREATE INDEX IF NOT EXISTS login_attempts_email_idx ON login_attempts (email)');
            $pdo->exec('CREATE INDEX IF NOT EXISTS login_attempts_ip_idx ON login_attempts (ip_address)');
        } else {
            $pdo->exec(<<<'SQL'
                CREATE TABLE IF NOT EXISTS `login_attempts` (
                    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `email`      VARCHAR(180)    NOT NULL,
                    `ip_address` VARCHAR(45)     NOT NULL,
                    `user_id`    INT UNSIGNED    NULL,
                    `successful` TINYINT(1)      NOT NULL DEFAULT 0,
                    `reason`     VARCHAR(100)    NULL,
                    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `login_attempts_email_idx` (`email`),
                    KEY `login_attempts_ip_idx` (`ip_address`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
        }
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS login_attempts');
    }
}
