<?php

declare(strict_types=1);

namespace Astral\AuthExtra\Throttle;

/**
 * Limite les tentatives de connexion par e-mail + IP.
 *
 * Usage typique dans AuthController::login() :
 *
 *   if ($throttler->tooManyAttempts($email, $ip)) {
 *       return $this->render('auth/login', [
 *           'errors' => ['email' => [
 *               sprintf('Trop de tentatives. Réessayez dans %d s.', $throttler->availableIn($email, $ip)),
 *           ]],
 *           'old' => ['email' => $email],
 *       ]);
 *   }
 *
 *   // échec → $throttler->hit($email, $ip);
 *   // succès → $throttler->clear($email, $ip);
 */
final class LoginThrottler
{
    public function __construct(
        private ThrottleStoreInterface $store,
        private int $maxAttempts = 5,
        private int $decaySeconds = 900,
    ) {}

    public function key(string $email, string $ip): string
    {
        return 'auth_throttle:' . sha1(strtolower(trim($email)) . '|' . $ip);
    }

    public function tooManyAttempts(string $email, string $ip): bool
    {
        return $this->attempts($email, $ip) >= $this->maxAttempts;
    }

    public function hit(string $email, string $ip): int
    {
        $key      = $this->key($email, $ip);
        $payload  = $this->read($key);
        $attempts = $payload['attempts'] + 1;

        $this->store->set($key, [
            'attempts'   => $attempts,
            'expires_at' => time() + $this->decaySeconds,
        ], $this->decaySeconds);

        return $attempts;
    }

    public function clear(string $email, string $ip): void
    {
        $this->store->forget($this->key($email, $ip));
    }

    public function attempts(string $email, string $ip): int
    {
        return $this->read($this->key($email, $ip))['attempts'];
    }

    public function retriesLeft(string $email, string $ip): int
    {
        return max(0, $this->maxAttempts - $this->attempts($email, $ip));
    }

    /** Secondes restantes avant déblocage (0 si non bloqué). */
    public function availableIn(string $email, string $ip): int
    {
        if (!$this->tooManyAttempts($email, $ip)) {
            return 0;
        }

        $expiresAt = $this->read($this->key($email, $ip))['expires_at'];

        return max(0, $expiresAt - time());
    }

    public function maxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function decaySeconds(): int
    {
        return $this->decaySeconds;
    }

    /** @return array{attempts: int, expires_at: int} */
    private function read(string $key): array
    {
        $data = $this->store->get($key);

        if (!is_array($data)) {
            return ['attempts' => 0, 'expires_at' => time() + $this->decaySeconds];
        }

        return [
            'attempts'   => (int) ($data['attempts'] ?? 0),
            'expires_at' => (int) ($data['expires_at'] ?? time() + $this->decaySeconds),
        ];
    }
}
