<?php

declare(strict_types=1);

namespace Astral\AuthExtra\Tests;

use Astral\AuthExtra\Throttle\ArrayThrottleStore;
use Astral\AuthExtra\Throttle\LoginThrottler;
use PHPUnit\Framework\TestCase;

final class LoginThrottlerTest extends TestCase
{
    private LoginThrottler $throttler;

    protected function setUp(): void
    {
        $this->throttler = new LoginThrottler(
            store: new ArrayThrottleStore(),
            maxAttempts: 3,
            decaySeconds: 60,
        );
    }

    public function testAllowsUnderLimit(): void
    {
        $this->throttler->hit('a@b.c', '1.1.1.1');
        $this->throttler->hit('a@b.c', '1.1.1.1');

        self::assertFalse($this->throttler->tooManyAttempts('a@b.c', '1.1.1.1'));
        self::assertSame(1, $this->throttler->retriesLeft('a@b.c', '1.1.1.1'));
    }

    public function testBlocksAtMaxAttempts(): void
    {
        $this->throttler->hit('a@b.c', '1.1.1.1');
        $this->throttler->hit('a@b.c', '1.1.1.1');
        $this->throttler->hit('a@b.c', '1.1.1.1');

        self::assertTrue($this->throttler->tooManyAttempts('a@b.c', '1.1.1.1'));
        self::assertGreaterThan(0, $this->throttler->availableIn('a@b.c', '1.1.1.1'));
    }

    public function testClearResetsAttempts(): void
    {
        $this->throttler->hit('a@b.c', '1.1.1.1');
        $this->throttler->hit('a@b.c', '1.1.1.1');
        $this->throttler->hit('a@b.c', '1.1.1.1');
        $this->throttler->clear('a@b.c', '1.1.1.1');

        self::assertFalse($this->throttler->tooManyAttempts('a@b.c', '1.1.1.1'));
        self::assertSame(0, $this->throttler->attempts('a@b.c', '1.1.1.1'));
    }

    public function testKeysAreCaseInsensitiveOnEmail(): void
    {
        $this->throttler->hit('Admin@Example.com', '10.0.0.1');

        self::assertSame(1, $this->throttler->attempts('admin@example.com', '10.0.0.1'));
    }
}
