<?php

declare(strict_types=1);

namespace Astral\AuthExtra\Throttle;

/**
 * Store en mémoire (tests / CLI).
 */
final class ArrayThrottleStore implements ThrottleStoreInterface
{
    /** @var array<string, array{value: mixed, expires_at: int}> */
    private array $items = [];

    public function get(string $key): mixed
    {
        if (!isset($this->items[$key])) {
            return null;
        }

        if ($this->items[$key]['expires_at'] < time()) {
            unset($this->items[$key]);

            return null;
        }

        return $this->items[$key]['value'];
    }

    public function set(string $key, mixed $value, int $ttlSeconds): void
    {
        $this->items[$key] = [
            'value'      => $value,
            'expires_at' => time() + $ttlSeconds,
        ];
    }

    public function forget(string $key): void
    {
        unset($this->items[$key]);
    }
}
