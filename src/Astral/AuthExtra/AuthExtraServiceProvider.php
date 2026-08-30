<?php

declare(strict_types=1);

namespace Astral\AuthExtra;

use Astral\AuthExtra\Logging\LoginAttemptLogger;
use Astral\AuthExtra\RememberMe\RememberMeManager;
use Astral\AuthExtra\RememberMe\RememberTokenRepository;
use Astral\AuthExtra\Throttle\CacheThrottleStore;
use Astral\AuthExtra\Throttle\LoginThrottler;
use Core\Cache;
use Core\Container;
use Core\Logger;
use Core\ServiceProviderInterface;
use PDO;

/**
 * Enregistre les services auth avancés dans le conteneur Astral.
 *
 * Config optionnelle via $appConfig['auth_extra'] :
 *   max_attempts   (int, défaut 5)
 *   decay_seconds  (int, défaut 900)
 *   remember_days  (int, défaut 30)
 *   log_to_database (bool, défaut false) — nécessite migration login_attempts
 */
final class AuthExtraServiceProvider implements ServiceProviderInterface
{
    /**
     * @param array<string, mixed> $appConfig
     * @param array<string, mixed> $dbConfig
     */
    public function register(Container $container, array $appConfig, array $dbConfig): void
    {
        /** @var array<string, mixed> $cfg */
        $cfg = is_array($appConfig['auth_extra'] ?? null) ? $appConfig['auth_extra'] : [];

        $maxAttempts  = (int) ($cfg['max_attempts'] ?? 5);
        $decaySeconds = (int) ($cfg['decay_seconds'] ?? 900);
        $rememberDays = (int) ($cfg['remember_days'] ?? 30);
        $logToDb      = (bool) ($cfg['log_to_database'] ?? false);

        $container->singleton(LoginThrottler::class, static function (Container $c) use ($maxAttempts, $decaySeconds): LoginThrottler {
            return new LoginThrottler(
                store: new CacheThrottleStore($c->make(Cache::class)),
                maxAttempts: $maxAttempts,
                decaySeconds: $decaySeconds,
            );
        });

        $container->singleton(LoginAttemptLogger::class, static function (Container $c) use ($logToDb): LoginAttemptLogger {
            return new LoginAttemptLogger(
                logger: $c->make(Logger::class),
                pdo: $logToDb ? $c->make(PDO::class) : null,
            );
        });

        $container->singleton(RememberTokenRepository::class, static function (Container $c): RememberTokenRepository {
            return new RememberTokenRepository($c->make(PDO::class));
        });

        $container->singleton(RememberMeManager::class, static function (Container $c) use ($rememberDays): RememberMeManager {
            return new RememberMeManager(
                repository: $c->make(RememberTokenRepository::class),
                lifetimeDays: $rememberDays,
            );
        });
    }
}
