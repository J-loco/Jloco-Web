# Phase 1 — Modern PHP foundation

**Status: done (2026-09-13).** Pages still render the same way; what changed is underneath.

## Tasks

- [x] **Docker image** (`JLoco-Web/Dockerfile`): `php:8.3-apache` with `gd`, `intl`, `opcache`,
      `pdo_mysql`; `mod_rewrite` + `mod_headers` enabled; production settings in `docker/php.ini`
      (`display_errors=Off`, errors to stderr, `expose_php=Off`), `ServerTokens Prod` in `docker/apache.conf`.
      The image holds the runtime and Composer dependencies only; the code stays bind-mounted.
- [x] **Composer** (`composer.json` / `composer.lock`), PSR-4 `JLoco\Web\` → `src/`. Dependencies are
      installed at image build into `/opt/jloco-web/vendor` (`JLOCO_VENDOR_DIR`), outside the bind
      mount; `include/autoload.php` registers `src/` at runtime. Outside Docker: `composer install` → `vendor/`.
- [x] **`declare(strict_types=1)`** in every new file (`src/`, `bin/`).
- [x] **Config**: `src/Config.php` (typed, readonly), read from real env > `JLoco-Web/.env`
      (phpdotenv, see `.env.example`) > defaults. `configuration/configuration.php` exposes `config()` and
      keeps the old constants as aliases for the legacy pages.
- [x] **Database**: `src/Database.php`, lazy `login()` / `game()`, `charset=utf8mb4` in the DSN,
      `ERRMODE_EXCEPTION`, `FETCH_OBJ` default, native prepared statements (`EMULATE_PREPARES=false`).
      `include/database.php` still gives the legacy pages `$login` / `$connection` / `$jiva`; the launcher
      endpoints and the captcha only open what they use.
- [x] **Least-privilege DB user** `jloco_web`: `SELECT` on both databases, writes only on the tables
      listed in `src/Migration/WebUserProvisioner.php`. Verified: `DROP`, `CREATE`,
      `DELETE FROM world_accounts` and `UPDATE world_players` are denied.
- [x] **Charset**: `utf8mb4` connection; accented text round-trips through the latin1 `world_accounts` table.
- [x] **Captcha back on** (S17): `src/Captcha.php` (GD, random code, case-insensitive, single use).
      The register modal loads its image only when opened, so it does not overwrite the register page's code.
- [x] **Logging**: PHP errors go to stderr → `docker compose logs jloco_web`.
- [x] **Web-owned migrations**: `JLoco-Web/migrations/*.sql` (moved from `JLoco-Game/db-init/11` and `12`),
      applied by `bin/migrate`, recorded in `website_migrations`. A file targets the login DB unless its first
      line is `-- database: game`; migrations must be re-runnable (`IF NOT EXISTS`).

Verified end to end under the `jloco_web` user: register (wrong captcha rejected, right captcha in
lowercase accepted, captcha not reusable), login with remember-me, profile toggle, wrong then right secret
answer, vote, purchase (points debited, gift written, purchase logged), drop search, job ladder,
remember-me restore, logout. Every page and both launcher endpoints return 200 with no PHP notice.

## How it runs

`JLoco-Game/docker-compose.yml`:

- `jloco_web_migrate` (one-off, same image): waits for the base schema from `db-init/`, applies pending
  migrations, then creates/updates `jloco_web` with `WEB_DB_PASSWORD`. It is the only web service that
  gets the root password.
- `jloco_web` starts only after the migration job succeeded, and connects as `jloco_web`.

```bash
cd JLoco-Game
# Build ONLY the web image: jloco_login currently also points at ../JLoco-Web (see below)
docker compose build jloco_web
docker compose up -d jloco_web              # runs jloco_web_migrate first
docker compose run --rm jloco_web_migrate   # re-run migrations / re-provision the DB user
```

`JLoco-Game/.env` needs `DB_ROOT_PASSWORD` and `WEB_DB_PASSWORD` (see `.env.example`).

## Found and fixed along the way

- Native prepared statements surfaced two sidebar bugs hidden by PDO emulation (audit D18): sub-area names
  were read from `jloco_game.subarea_data.name` (no such column; names are in
  `jloco_login.world_base_sub_areas`), and the wanted-list query had an unescaped `','` that passed two
  arguments to `prepare()`. Both now go through `sidebar_locator()` in `include/rightmenu.php`.
- `src/`, `bin/`, `migrations/`, `docker/` are denied over HTTP like the other internal folders.

## Known issue outside the portal

`jloco_login` in `JLoco-Game/docker-compose.yml` has `build: context: ../JLoco-Web`: a global
`docker compose build` / `up --build` would replace the login server with the web image. Point it at
`../JLoco-Login` (or restore `image: jloco/login:latest`) before building everything.

## Left for Phase 2

- A self-contained production image (code copied in rather than bind-mounted) comes with the `public/`
  document root, so that only `public/` is web-served.
