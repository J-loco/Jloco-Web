# Phase 3 — Quality, tests, password migration

Status: done (2026-09-14). The only follow-up left is turning PBKDF2 on (see "Password hashing").

## Tasks

- [x] **PHP 8.4**: image `php:8.4-apache`, `composer.json` requires `^8.4` (platform `8.4.1`). This upgrade let
      us use current major versions (PHPUnit 13, Symfony Mailer 8) without pinning older ones.
- [x] **Static analysis**: PHPStan level 6 on `src/`, `config/`, `public/index.php`, the launcher scripts,
      `bin/` and `tests/` (`phpstan.neon.dist`).
- [x] **Code style**: php-cs-fixer, PSR-12 plus a few modern rules (`.php-cs-fixer.dist.php`).
- [x] **Automated upgrades**: Rector (PHP 8.4 set, dead code, type declarations), dry run in CI (`rector.php`).
- [x] **Typed models**: repositories return readonly models from `src/Model/` (`Account`, `Character`,
      `ShopItem`, `Guild`, `NewsPost`, …) built with `fromRow()`. There is no `stdClass` left in controllers or templates.
- [x] **Unit tests (PHPUnit 13)**, `tests/Unit`: `Experience`, `ItemEffects` and character labels,
      `Support\Text`, `PasswordHasher` (vectors shared with the Java tests), router URL generation and
      `LegacyUrls` redirects, and pure domain logic (`ServerStatus::formatUptime`, `ForumFeed::parse`,
      `JobLadder::rank`, `Drop` grouping, `Config` parsing).
- [x] **Integration tests**, `tests/Integration`: repositories, `ShopService` (missing points,
      unavailable items, atomic debit, gift delivery, refund when delivery fails), `AuthService`
      (registration and password rules, latin1 guards, throttle, legacy→PBKDF2 rehash, secret-answer and
      email-link resets, expired links), vote cooldown per account and IP, migrations, and the grants of the
      `starloco_web` DB user. Remember-me rotation is not covered yet.
      They create throwaway `starloco_login_test` / `starloco_game_test` databases from
      `tests/Integration/schema/*.sql`, which were dumped from the real schemas after every `db-init` script ran.
- [x] **CI** (`.github/workflows/ci.yml`): `quality` (cs, PHPStan, Rector, unit tests), `integration`
      (MariaDB 11.3 service), `images` (builds the `production` target, so the Tailwind build and the Dockerfile
      are covered), `experience` (see below).
- [x] **Experience table sync**: `bin/sync-experience [--check] [path/to/Experience.lua]` regenerates
      `src/Game/Experience.php`. The CI job runs only when the `STARLOCO_GAME_TOKEN` secret (read-only token for
      the private StarLoco-Game repository) is set. Otherwise it prints a skip message.
- [x] **Password hashing migration**, portal and StarLoco-Login. Details below.
- [x] **Email password reset**: a selector/token link (`/password/reset/{selector}/{token}`) is sent through
      Symfony Mailer. The table is `website_password_resets` (migration 003): SHA-256 of the token, 60 minutes,
      single use, and every link for the account is invalidated on success. Throttled like login. The
      secret question/answer form stays as the fallback while `MAILER_DSN` is empty.
- [x] **Item images**: `bin/item-sprites` lists the (type, skin) pairs sold in the shop.
      `starloco_web_sprites` renders them from the client SWFs (JPEXS FFDec + ImageMagick) to
      `public/assets/img/items/<type>/<skin>.png`, and the Twig `item_image()` function falls back to a
      placeholder. There are 389 images; one sprite with an empty first frame is skipped.
      Guild emblems were not exported: the ladder has no emblem column to show them in.
- [x] **Shop servers**: `SHOP_SERVERS="1:starloco_game,2:other_game_db"` maps `website_shop_objects.server` to
      the game database that receives the gift. `bin/migrate` grants `gifts` write access in each of them.

## Password hashing

Two formats are understood by both the portal (`Security\PasswordHasher`) and StarLoco-Login
(`login/packet/Password.java`):

| Scheme | Stored value |
|---|---|
| `legacy` | `hex(SHA512(hex(MD5(password))))` (unchanged) |
| `pbkdf2` | `pbkdf2_sha512$210000$<base64 salt>$<base64 key>` (PBKDF2-HMAC-SHA512, 16-byte salt, 64-byte key) |

PBKDF2 was chosen over argon2id because Java 8 has it built in (`PBKDF2WithHmacSHA512`): the login server
needs no new dependency. The iteration count is part of the stored value, so it can be raised later.

The configured scheme decides what new hashes look like and triggers a rehash on successful login, from
either the site or the game client:

- portal: `PASSWORD_HASH_SCHEME` (compose: `WEB_PASSWORD_HASH_SCHEME`)
- login server: `system.server.login.password.scheme` in `login.config.properties`

**Both are still `legacy`.** Rollout:

1. Deploy the new login server and portal with `legacy`. Both verify either format.
2. Set both settings to `pbkdf2` and restart. Accounts upgrade as players log in.
3. Do not go back to an old login server image (`starloco/login:latest` from Docker Hub): it cannot
   verify PBKDF2 hashes.
4. Optional, later: once most accounts have migrated, force a reset for the accounts still on the
   legacy hash (`SELECT COUNT(*) FROM world_accounts WHERE pass NOT LIKE 'pbkdf2_sha512$%'`).

## StarLoco-Login changes made for this phase

- `Password.java`: `matches()`, `needsRehash()`, `hash(scheme)`. `verify()` rehashes through
  `AccountData.updatePassword()`. The legacy hash is now computed on UTF-8 bytes (it used the
  platform charset before).
- `AccountData.update()` used string-concatenated SQL and wrote the password back from memory. A password
  changed on the site while the player was connected was reverted on the next update. It now uses bound
  parameters and writes only `banned`, `bannedTime`, `pseudo`, `logged`, `subscribe`.
- `Account`: a NULL `pseudo` becomes `""`. The old server wrote the literal string `'null'`, which then
  counted as a chosen nickname.
- JUnit 4 tests (`test/`, `gradle test`), a multi-stage `Dockerfile` (tests run during the build) and
  `.github/workflows/ci.yml`. Compose now builds `starloco/login:local` from `../StarLoco-Login`.

## Bugs found while testing

- Registration accepted `_` in account names but the login server rejects it, so such accounts could never
  log in in game. The name rule now matches the login server: `[A-Za-z0-9.@-]{3,30}`.
- Non-latin1 or invalid UTF-8 input caused "Illegal mix of collations" 500s against latin1 columns. Input is
  scrubbed and checked with `Support\Text::fitsLatin1()`.
- `Router::url()` broke on regex quantifiers inside placeholders (`{token:[a-f0-9]{64}}` produced a
  trailing `}`). It now uses FastRoute's own route parser, and a regression test covers it.
- Production ini ordering: `php-production.ini` was loaded before `zz-starloco.ini`, so
  `opcache.validate_timestamps` stayed on. The file was renamed to `zzz-starloco-production.ini`.

## How to run

From `StarLoco-Game/` (vendor lives in the image, integration tests use the compose MariaDB):

```bash
docker compose build starloco_web_tools
docker compose run --rm starloco_web_tools                 # composer check: everything CI runs
docker compose run --rm starloco_web_tools test:unit       # or test, test:integration, stan, cs, cs:fix, rector, rector:fix
docker compose run --rm starloco_web_tools experience:check

docker compose run --rm starloco_web_tools item-sprites    # writes var/item-sprites.tsv
docker compose build starloco_web_sprites
docker compose run --rm starloco_web_sprites               # renders missing item images (--force: all)

docker compose --profile mail up -d starloco_mailpit       # catches reset emails at http://127.0.0.1:8025
# with WEB_MAILER_DSN=smtp://starloco_mailpit:1025 and WEB_MAIL_FROM=noreply@starloco.local in .env

docker compose build starloco_login                        # runs the Java tests
```

Without Docker: `composer install && composer check` (integration tests need `TEST_DB_HOST`, `TEST_DB_USER`,
`TEST_DB_PASS` for a MariaDB account allowed to create databases).
