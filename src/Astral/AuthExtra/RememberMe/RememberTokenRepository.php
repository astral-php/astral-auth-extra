<?php

declare(strict_types=1);

namespace Astral\AuthExtra\RememberMe;

use PDO;

/**
 * Persistance des tokens remember-me (table `remember_tokens`).
 */
final class RememberTokenRepository
{
    public function __construct(private PDO $pdo) {}

    public function store(string $selector, string $tokenHash, int $userId, int $expiresAt): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO remember_tokens (selector, token_hash, user_id, expires_at, created_at)
             VALUES (:selector, :token_hash, :user_id, :expires_at, :created_at)'
        );

        $stmt->execute([
            'selector'   => $selector,
            'token_hash' => $tokenHash,
            'user_id'    => $userId,
            'expires_at' => date('Y-m-d H:i:s', $expiresAt),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return array{selector: string, token_hash: string, user_id: int|string, expires_at: int}|null
     */
    public function findValid(string $selector): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT selector, token_hash, user_id, expires_at
             FROM remember_tokens
             WHERE selector = :selector
             LIMIT 1'
        );
        $stmt->execute(['selector' => $selector]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        $expiresTs = strtotime((string) $row['expires_at']);
        if ($expiresTs === false || $expiresTs < time()) {
            $this->deleteBySelector($selector);

            return null;
        }

        return [
            'selector'   => (string) $row['selector'],
            'token_hash' => (string) $row['token_hash'],
            'user_id'    => $row['user_id'],
            'expires_at' => $expiresTs,
        ];
    }

    public function rotate(string $selector, string $newTokenHash): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE remember_tokens SET token_hash = :token_hash WHERE selector = :selector'
        );
        $stmt->execute([
            'token_hash' => $newTokenHash,
            'selector'   => $selector,
        ]);
    }

    public function deleteBySelector(string $selector): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM remember_tokens WHERE selector = :selector');
        $stmt->execute(['selector' => $selector]);
    }

    public function deleteByUserId(int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM remember_tokens WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
    }
}
