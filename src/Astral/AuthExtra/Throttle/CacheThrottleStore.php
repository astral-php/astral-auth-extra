<?php

declare(strict_types=1);

namespace Astral\AuthExtra\Throttle;

use Core\Cache;

/**
 * Adaptateur autour du cache fichier d'Astral MVC.
 */
final class CacheThrottleStore implements ThrottleStoreInterface
{
    public function __construct(private Cache $cache) {}

    public function get(string $key): mixed
    {
        return $this->cache->get($key);
    }

    public function set(string $key, mixed $value, int $ttlSeconds): void
    {
        $this->cache->set($key, $value, $ttlSeconds);
    }

    public function forget(string $key): void
    {
        $this->cache->forget($key);
    }
}
