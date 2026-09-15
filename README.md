# JLoco Web

JLoco Web is the web portal for the JLoco Dofus 1.39 server stack. It provides account registration and management, server status, ladders, drop search, voting, a points shop, news administration, and the HTTP endpoints used by the desktop launcher and game client.

The application is written for PHP 8.4 and uses FastRoute, Twig, PDO, Symfony Mailer, and Tailwind CSS. It is designed to run alongside [JLoco Game](https://github.com/JLoco/JLoco) and JLoco Login, which own the game and account databases.

## Features

- Account registration, login, remember-me sessions, password changes, and password resets
- PvM, PvP, guild, profession, and vote ladders
- Searchable monster drop tables
- Vote rewards and optional Dedipass integration
- Multi-server points shop with in-game gift delivery
- News and game-news administration
- Live login/game server status
- Launcher news, status, manifest, and update-file endpoints
- Dofus client language files under the stable `/lang/` URL

## Quick start with Docker

The recommended development setup uses the Compose stack in a sibling `JLoco-Game` checkout. The directories should be arranged like this:

```text
workspace/
├── JLoco-Game/
├── JLoco-Login/
└── JLoco-Web/
```

Docker and Docker Compose are the only host requirements for this workflow.

1. Create the stack environment file:

   ```bash
   cd ../JLoco-Game
   cp .env.example .env
   ```

2. Set at least `DB_ROOT_PASSWORD` and `WEB_DB_PASSWORD` in `JLoco-Game/.env`. Review the remaining `WEB_*` settings if the portal will not use the defaults.

3. Build and start the stack:

   ```bash
   docker compose up -d --build
   ```

4. Open <http://127.0.0.1/dofus/>.

Compose initializes MariaDB, applies the web migrations, provisions a least-privilege `jloco_web` database user, builds the Tailwind stylesheet, and starts the portal. View portal logs with:

```bash
docker compose logs -f jloco_web
```

### Start only the portal dependencies

If the game and login services are not needed, Compose can start the database and portal path only:

```bash
docker compose up -d jloco_web
```

The server-status widgets will show the absent game services as offline.

## Configuration

All application settings are documented in [`.env.example`](.env.example). When running PHP directly, copy it to `.env`; real environment variables take precedence over values in that file.

The Compose stack reads `JLoco-Game/.env` instead. Portal settings use the same names with a `WEB_` prefix, for example:

| Direct PHP | Docker Compose | Purpose |
| --- | --- | --- |
| `APP_URL` | `WEB_APP_URL` | Public portal URL, including its path |
| `APP_DEBUG` | `WEB_APP_DEBUG` | Detailed development error pages |
| `SITE_NAME` | `WEB_SITE_NAME` | Name displayed by the portal |
| `ADMIN_ACCOUNT_ID` | `WEB_ADMIN_ACCOUNT_ID` | Account allowed to access `/admin` |
| `GAME_SERVER_ID` | `WEB_GAME_SERVER_ID` | World server shown by the portal |
| `SHOP_SERVERS` | `WEB_SHOP_SERVERS` | Shop server ID-to-database mappings |
| `PASSWORD_HASH_SCHEME` | `WEB_PASSWORD_HASH_SCHEME` | Hash format for new passwords |
| `MAILER_DSN` | `WEB_MAILER_DSN` | Symfony Mailer transport DSN |
| `MAIL_FROM` | `WEB_MAIL_FROM` | Password-reset sender address |

`WEB_APP_BASE_PATH` configures the Apache mount point and must match the path in `WEB_APP_URL`. The defaults mount the site at `/dofus`.

Only enable `TRUST_CLOUDFLARE`/`WEB_TRUST_CLOUDFLARE` when requests actually pass through Cloudflare. Enabling it on a directly reachable server allows clients to forge their source address.

## Development

### Requirements without Docker

- PHP 8.4
- Composer 2
- PHP extensions: `gd`, `intl`, `pdo`, and `pdo_mysql`
- MariaDB containing the JLoco login and game schemas
- A web server whose document root is `public/` and whose unknown routes fall back to `public/index.php`

Install PHP dependencies with:

```bash
composer install
```

Never expose the repository root through the web server; only `public/` is intended to be web-accessible. The supplied [Apache configuration](docker/apache.conf) shows the expected alias and fallback routing.

### Quality checks

From `JLoco-Game`, run the complete CI-equivalent check in the development image:

```bash
docker compose run --rm jloco_web_tools check
```

Individual Composer scripts can be passed to the same service:

```bash
docker compose run --rm jloco_web_tools test:unit
docker compose run --rm jloco_web_tools test:integration
docker compose run --rm jloco_web_tools stan
docker compose run --rm jloco_web_tools cs
docker compose run --rm jloco_web_tools cs:fix
docker compose run --rm jloco_web_tools rector
```

Integration tests create and remove dedicated `jloco_login_test` and `jloco_game_test` databases. The Compose tools service supplies the required database settings automatically.

### CSS assets

The generated `public/assets/app.css` file is intentionally ignored by Git. Rebuild it after changing Twig templates, JavaScript class references, or `assets/css/app.css`:

```bash
cd ../JLoco-Game
docker compose run --rm jloco_web_assets
```

The `production` Docker target performs this build and includes the resulting stylesheet in a self-contained image.

### Database migrations

Add schema changes as re-runnable SQL files in `migrations/`, using the next numeric prefix. Migrations target the login database by default; add this first line when a migration belongs to the game database:

```sql
-- database: game
```

Apply pending migrations and refresh the portal database grants with:

```bash
cd ../JLoco-Game
docker compose run --rm jloco_web_migrate
```

### Development email

To inspect password-reset messages locally, set these values in `JLoco-Game/.env`:

```dotenv
WEB_MAILER_DSN=smtp://jloco_mailpit:1025
WEB_MAIL_FROM=noreply@jloco.local
```

Then start Mailpit and open <http://127.0.0.1:8025>:

```bash
docker compose --profile mail up -d jloco_mailpit
```

### Shop item images

The optional sprite tools derive the list of items sold by the shop and export their images from the sibling JLoco Client checkout:

```bash
cd ../JLoco-Game
docker compose run --rm jloco_web_tools item-sprites
docker compose run --rm jloco_web_sprites
```

## Architecture

```text
public/index.php
    -> src/Kernel.php
        -> config/routes.php
            -> src/Controller/
                -> src/Service/ and src/Repository/
                    -> templates/
```

- `public/` is the only web root and contains the front controller and static assets.
- `src/Controller/` handles HTTP actions and returns response objects.
- `src/Repository/` contains page-level SQL and maps data to readonly models.
- `src/Service/` contains application workflows and external integrations.
- `templates/` contains auto-escaped Twig views and shared UI components.
- `config/routes.php` defines named routes used by controllers and templates.
- `migrations/` contains idempotent portal schema changes.
- `tests/Unit/` and `tests/Integration/` cover domain and database behavior.

The portal reads and writes the existing JLoco login and game databases; it is not a standalone account or game server.

## Stable client endpoints

The following public paths are consumed outside the website and must remain stable:

- `/dofus/lang/` serves Dofus client language/version data.
- `/dofus/launcher/status.php` reports server status.
- `/dofus/launcher/news.php` supplies launcher news.
- `/dofus/launcher/manifest.json` describes launcher downloads.
- `/dofus/launcher/files/` contains launcher-managed files.

See [the launcher endpoint documentation](docs/launcher-endpoints.md) before changing their payloads or caching behavior.

## License

This project is proprietary. See [`composer.json`](composer.json) for package metadata.
