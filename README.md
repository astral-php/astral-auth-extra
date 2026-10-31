# astral-auth-extra

Auth avancée optionnelle pour [Astral MVC](https://github.com/astral-php/astral) :
**throttling** login, **remember-me**, **journal** des tentatives.

Le core Astral garde une auth simple (`Core\Auth\Auth`). Ce package ajoute
les garde-fous sans alourdir le framework.

[![PHP](https://img.shields.io/badge/PHP-8.1%E2%80%938.5-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![Version](https://img.shields.io/badge/version-0.2.0-blue)](./CHANGELOG.md)

> **Statut :** MVP local (Phase C) — pas encore publié sur Packagist.

## Compatibilité

- PHP : `^8.1` (8.1 → 8.5)
- Astral MVC : `^1.2` (ServiceProvider + Cache + Logger + PDO)

## Installation (quand publié)

```bash
composer require astral-php/astral-auth-extra
```

En local (workspace `components-astral`) :

```json
"repositories": [
  { "type": "path", "url": "../components-astral/astral-auth-extra" }
]
```

```bash
composer require astral-php/astral-auth-extra:@dev
```

### ServiceProvider

Dans `config/dependencies.php` :

```php
use Astral\AuthExtra\AuthExtraServiceProvider;

return [
    // …
    AuthExtraServiceProvider::class,
];
```

### Config optionnelle (`config/app.php`)

```php
'auth_extra' => [
    'max_attempts'    => 5,
    'decay_seconds'   => 900,   // 15 min
    'remember_days'   => 30,
    'log_to_database' => false, // true → table login_attempts
],
```

### Migrations

Copier (ou symlink) les fichiers de `database/migrations/` vers le projet hôte,
puis :

```bash
php bin/console migrate
```

- `login_attempts` — journal DB (si `log_to_database = true`)
- `remember_tokens` — obligatoire pour remember-me

## Fonctionnalités MVP

| Service | Rôle |
|---------|------|
| `LoginThrottler` | Bloque après N échecs (e-mail + IP), via Cache Astral |
| `LoginAttemptLogger` | Log fichier (+ DB optionnelle) |
| `RememberMeManager` | Cookie HttpOnly selector:validator + rotation |

Voir `examples/login-integration.php` pour le branchement dans `AuthController`.

## 2FA

Hors MVP — prévu en itération ultérieure du même package.

## Tests

```bash
composer install
composer test
```
