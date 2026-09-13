# Phase 1 — Modern PHP foundation

Scope: tooling and infrastructure the later phases build on. Pages still render the same way.

## Tasks

- [ ] **Docker image**: `php:8.3-apache`, enable `mod_rewrite` and `mod_headers`, install `gd`
      (captcha), `intl`; production `php.ini` (`display_errors=Off`, `log_errors=On`).
- [ ] **Composer** with PSR-4 autoload (`StarLoco\Web\` → `src/`); `vendor/` git-ignored and
      installed at image build.
- [ ] **`declare(strict_types=1)`** in every new file.
- [ ] **Config**: `vlucas/phpdotenv` + a typed `Config` class replacing the `define()` constants in
      `configuration/configuration.php` (keep the constants as thin aliases until Phase 2 is done).
- [ ] **Database**: `Database` class creating PDO connections lazily (login, game), with
      `ERRMODE_EXCEPTION`, `FETCH_OBJ` default, `EMULATE_PREPARES=false`, `charset=utf8mb4` in the DSN.
      Removes the duplicate `$connection` (same DB as `$login`).
- [ ] **Least-privilege DB user** `starloco_web`: `SELECT` on both DBs, `INSERT/UPDATE/DELETE` only
      on the tables the portal writes (`world_accounts`, `website_*`, `client_rss_news`, `gifts`).
- [ ] **Charset**: `utf8mb4` at the connection level (`utf8_encode` was already removed in Phase 0);
      check `world_accounts` (latin1 table) round-trips accented pseudos.
- [ ] **Captcha back on** (S17) with GD, or replace with a honeypot + throttle.
- [ ] **Logging**: `error_log` to stderr so `docker compose logs starloco_web` shows app errors.
- [ ] **Web-owned migrations** move from `StarLoco-Game/db-init/` to `StarLoco-Web/migrations/`
      with a tiny runner (`bin/migrate`) that records applied files in `website_migrations`.

## Done when

- `composer install` in the image, autoload works, `Config` and `Database` used by `index.php`.
- No `define('…_PASS'…)` left; captcha works.
