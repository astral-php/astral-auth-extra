<?php

declare(strict_types=1);

namespace Astral\AuthExtra\Throttle;

/**
 * Stockage minimal pour le compteur de tentatives de login.
 */
interface ThrottleStoreInterface
{
    public function get(string $key): mixed;

    public function set(string $key, mixed $value, int $ttlSeconds): void;

    public function forget(string $key): void;
}
