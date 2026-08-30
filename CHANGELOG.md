# Changelog

## [0.1.0] — 2026-08-30

### Ajouté

- `LoginThrottler` — limitation des tentatives de connexion (store injectable).
- `LoginAttemptLogger` — journalisation succès / échec (Logger Astral ou callable).
- `RememberMeManager` — cookie « se souvenir de moi » (selector + hash token, PDO).
- Migrations `login_attempts` et `remember_tokens` (SQLite + MySQL).
- `AuthExtraServiceProvider` — enregistrement DI optionnel.
