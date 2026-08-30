<?php

declare(strict_types=1);

namespace Astral\AuthExtra\RememberMe;

/**
 * Gestion du cookie « Se souvenir de moi ».
 *
 * Format cookie : selector:validator (clair).
 * En base : selector + hash(validator), user_id, expires_at.
 *
 * Le contrôleur / middleware doit :
 * 1. Au login réussi avec remember=1 → create() + poser le cookie
 * 2. Au boot si guest → userId = authenticateFromCookie() puis Auth::login
 * 3. Au logout → forget() + effacer le cookie
 */
final class RememberMeManager
{
    public const COOKIE_NAME = 'astral_remember';

    public function __construct(
        private RememberTokenRepository $repository,
        private int $lifetimeDays = 30,
        private string $cookieName = self::COOKIE_NAME,
    ) {}

    /**
     * Crée un token et retourne la valeur cookie à envoyer.
     *
     * @return array{cookie_value: string, expires_at: int}
     */
    public function create(int $userId): array
    {
        $selector  = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        $expiresAt = time() + ($this->lifetimeDays * 86400);

        $this->repository->store(
            selector: $selector,
            tokenHash: hash('sha256', $validator),
            userId: $userId,
            expiresAt: $expiresAt,
        );

        return [
            'cookie_value' => $selector . ':' . $validator,
            'expires_at'   => $expiresAt,
        ];
    }

    /**
     * Valide le cookie et retourne l'user_id, ou null.
     * Rotation du validator à chaque succès (réduit le vol de cookie).
     *
     * @return array{user_id: int, cookie_value: string, expires_at: int}|null
     */
    public function authenticateFromCookie(?string $rawCookie): ?array
    {
        if ($rawCookie === null || $rawCookie === '') {
            return null;
        }

        $parts = explode(':', $rawCookie, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$selector, $validator] = $parts;
        $row = $this->repository->findValid($selector);

        if ($row === null) {
            return null;
        }

        if (!hash_equals($row['token_hash'], hash('sha256', $validator))) {
            $this->repository->deleteBySelector($selector);

            return null;
        }

        // Rotation
        $newValidator = bin2hex(random_bytes(32));
        $this->repository->rotate($selector, hash('sha256', $newValidator));

        return [
            'user_id'      => (int) $row['user_id'],
            'cookie_value' => $selector . ':' . $newValidator,
            'expires_at'   => (int) $row['expires_at'],
        ];
    }

    public function forget(?string $rawCookie): void
    {
        if ($rawCookie === null || $rawCookie === '') {
            return;
        }

        $parts = explode(':', $rawCookie, 2);
        if (count($parts) !== 2) {
            return;
        }

        $this->repository->deleteBySelector($parts[0]);
    }

    public function forgetAllForUser(int $userId): void
    {
        $this->repository->deleteByUserId($userId);
    }

    public function cookieName(): string
    {
        return $this->cookieName;
    }

    public function lifetimeDays(): int
    {
        return $this->lifetimeDays;
    }

    /**
     * Construit l'en-tête Set-Cookie (HttpOnly, SameSite=Lax, Secure si HTTPS).
     */
    public function buildSetCookieHeader(string $value, int $expiresAt, bool $secure = false): string
    {
        $parts = [
            $this->cookieName . '=' . rawurlencode($value),
            'Expires=' . gmdate('D, d M Y H:i:s T', $expiresAt),
            'Path=/',
            'HttpOnly',
            'SameSite=Lax',
        ];

        if ($secure) {
            $parts[] = 'Secure';
        }

        return implode('; ', $parts);
    }

    public function buildClearCookieHeader(bool $secure = false): string
    {
        return $this->buildSetCookieHeader('', 1, $secure);
    }
}
