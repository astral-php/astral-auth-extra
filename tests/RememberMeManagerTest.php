<?php

declare(strict_types=1);

namespace Astral\AuthExtra\Tests;

use Astral\AuthExtra\RememberMe\RememberMeManager;
use Astral\AuthExtra\RememberMe\RememberTokenRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class RememberMeManagerTest extends TestCase
{
    private PDO $pdo;
    private RememberMeManager $manager;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec(<<<'SQL'
            CREATE TABLE remember_tokens (
                id INTEGER PRIMARY KEY,
                selector TEXT NOT NULL UNIQUE,
                token_hash TEXT NOT NULL,
                user_id INTEGER NOT NULL,
                expires_at TEXT NOT NULL,
                created_at TEXT NOT NULL
            )
        SQL);

        $this->manager = new RememberMeManager(
            repository: new RememberTokenRepository($this->pdo),
            lifetimeDays: 7,
        );
    }

    public function testCreateAndAuthenticate(): void
    {
        $created = $this->manager->create(42);
        $result  = $this->manager->authenticateFromCookie($created['cookie_value']);

        self::assertNotNull($result);
        self::assertSame(42, $result['user_id']);
        self::assertNotSame($created['cookie_value'], $result['cookie_value']);
    }

    public function testRejectsTamperedCookie(): void
    {
        $created = $this->manager->create(1);
        [$selector] = explode(':', $created['cookie_value'], 2);

        self::assertNull($this->manager->authenticateFromCookie($selector . ':deadbeef'));
    }

    public function testForgetRemovesToken(): void
    {
        $created = $this->manager->create(5);
        $this->manager->forget($created['cookie_value']);

        self::assertNull($this->manager->authenticateFromCookie($created['cookie_value']));
    }

    public function testBuildSetCookieHeader(): void
    {
        $header = $this->manager->buildSetCookieHeader('abc:def', time() + 3600, true);

        self::assertStringContainsString('astral_remember=', $header);
        self::assertStringContainsString('HttpOnly', $header);
        self::assertStringContainsString('Secure', $header);
        self::assertStringContainsString('SameSite=Lax', $header);
    }
}
