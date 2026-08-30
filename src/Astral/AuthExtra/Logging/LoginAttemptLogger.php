<?php

declare(strict_types=1);

namespace Astral\AuthExtra\Logging;

use Core\Logger;
use PDO;

/**
 * Journalise les tentatives de connexion.
 *
 * - Toujours : Logger Astral (fichier) si fourni.
 * - Optionnel : table `login_attempts` via PDO (migration fournie).
 */
final class LoginAttemptLogger
{
    public function __construct(
        private ?Logger $logger = null,
        private ?PDO $pdo = null,
    ) {}

    public function success(string $email, string $ip, ?int $userId = null): void
    {
        $this->write(true, $email, $ip, $userId, null);
    }

    public function failure(string $email, string $ip, string $reason = 'invalid_credentials'): void
    {
        $this->write(false, $email, $ip, null, $reason);
    }

    private function write(
        bool $successful,
        string $email,
        string $ip,
        ?int $userId,
        ?string $reason,
    ): void {
        $context = [
            'email'      => $email,
            'ip'         => $ip,
            'user_id'    => $userId,
            'successful' => $successful,
            'reason'     => $reason,
        ];

        $this->logger?->info(
            $successful ? 'Login success' : 'Login failure',
            $context,
        );

        if ($this->pdo === null) {
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO login_attempts (email, ip_address, user_id, successful, reason, created_at)
             VALUES (:email, :ip, :user_id, :successful, :reason, :created_at)'
        );

        $stmt->execute([
            'email'      => $email,
            'ip'         => $ip,
            'user_id'    => $userId,
            'successful' => $successful ? 1 : 0,
            'reason'     => $reason,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
