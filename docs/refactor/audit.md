# Audit (2026-09-13)

State of StarLoco-Web before the refactor: ~3,700 lines across 14 pages, PHP 8.2 in Docker
(`php:8.2-apache`), two PDO connections to `starloco_login` (`$login`, `$connection`) and one to
`starloco_game` (`$jiva`), opened on every request.

## Security

| # | Finding | Where | Severity |
|---|---|---|---|
| S1 | Router includes any path: `?page=../launcher/status` loads a file outside `pages/` | `index.php` | High |
| S2 | ~21 queries built by concatenating values into SQL (some from `$_GET`/`$_POST`, e.g. the jobs ladder `LIKE '%" . $_POST['job'] . "%'`, vote IP) | `buy.php`, `shop.php`, `vote.php`, `ladder.php`, `rightmenu.php`, `launcher/news.php` | High |
| S3 | No CSRF protection on any form; state changes over GET (news deletion, logout, vote) | all forms, `administration.php`, `signin.php`, `vote.php` | High |
| S4 | Profile password change accepted the secret answer of *any* account | `profile.php` | High (fixed 2026-09-13) |
| S5 | Unescaped output of user-controlled data (XSS): account names, character names, secret question, `$_POST` values echoed back | `password.php`, `profile.php`, `ladder.php`, `shop.php`, `buy.php`, `viewdrop.php` | High |
| S6 | "Remember me" stores a password-derived hash in a cookie (and never matches, so it is also broken) | `index.php` | Medium |
| S7 | No session hardening: no `session_regenerate_id()` on login, default cookie flags | `index.php` | Medium |
| S8 | No brute-force protection on login, password reset, or secret-answer checks | `index.php`, `password.php`, `profile.php` | Medium |
| S9 | Shop purchase: read points → compute → write, no atomic check; two fast clicks spend the same points twice | `buy.php` | Medium |
| S10 | Vote cooldown trusts `CF-Connecting-IP` even when not behind Cloudflare (spoofable → unlimited points) | `vote.php`, `CLOUDFLARE_ENABLE` | Medium |
| S11 | Internal folders reachable over HTTP; `configuration/.htaccess` uses invalid syntax (returns 500 instead of 403); `class/`, `include/`, `pages/` not protected | `.htaccess` files | Medium |
| S12 | DB errors printed to visitors (`die('Error : ' . $e->getMessage())`) | `configuration.php` | Low |
| S13 | Secrets committed in `docker-compose.yml` (MariaDB root password); portal connects as `root` | `StarLoco-Game/docker-compose.yml` | Medium |
| S14 | Password reset uses `rand()` and `UPDATE ... WHERE account LIKE ?` | `password.php` | Low |
| S15 | Dedipass API called over plain HTTP | `profile.php` | Low |
| S16 | No security headers (clickjacking, MIME sniffing) | — | Low |
| S17 | Captcha disabled (and cannot work: GD extension missing from the image) | `register.php`, `img/captcha.php`, `Dockerfile` | Low |
| S18 | Whole git repository downloadable (`/dofus/.git/config` returned 200), plus `Dockerfile` and docs | web root | High |

## Correctness / schema drift

The code predates the current `starloco_login` / `starloco_game` schemas.

| # | Finding | Where |
|---|---|---|
| D1 | `experience` table dropped by game migration 05 (XP moved to Lua) | `profile.php`, `ladder.php` (fixed 2026-09-13, `class/Experience.class.php`) |
| D2 | `world_accounts.showOrHide` / `showOrHidePos` did not exist | fixed by `db-init/11-update_login_web_portal.sql` |
| D3 | `website_shop_purchase` → real table is `website_shop_objects_purchases (account, template, quantite, server, date DATETIME)` | `buy.php` |
| D4 | `website_shop_points_purchase` → `website_shop_points_purchases`; `date` is DATETIME but code inserts `d/m/Y H:i` | `profile.php` |
| D5 | `client_rss_news.title` → real column is `title_fr` (admin "game news" insert/list broken) | `administration.php` |
| D6 | `website_timeline_news.date` is DATETIME but admin inserts `d-m-Y` | `administration.php` |
| D7 | `world.entity.guilds` → `world_guilds` | `ladder.php` (fixed 2026-09-13) |
| D8 | `subarea_data` queried on the login DB (lives in game DB) | `rightmenu.php` (fixed 2026-09-13) |
| D9 | Server id `601` and name "Jiva" hard-coded; DB says 601 is "Eratz" | `rightmenu.php` |
| D10 | `?page=cgu` linked from footer/register/join but no `pages/cgu.php` | — |
| D11 | Register modal inputs have no `name` attributes (the modal form cannot work) | `include/footer.php` |
| D12 | `PAGE_WITHOUT_RIGHT_MENU` uses `strpos` on a string, so any page name that is a substring matches | `index.php` |
| D13 | Shop items use `website_shop_objects.server = 1` (a game-DB index), but the server dropdown listed `world_servers` ids (601…), so no item could ever be shown | `shop.php` |
| D14 | Admin news inserts never worked: `website_timeline_news` requires `title_en/_es`, `content_en/_es`; `client_rss_news.id` is not AUTO_INCREMENT and `link` has no default | `administration.php` |
| D15 | Forum news page returned 500: empty `URL_RSS_NEWS_IPB` passed to `DOMDocument::load()`; external feed HTML printed unescaped | `pages/news.php`, `include/rsslib.php` |
| D16 | `display_errors` on in the image: the `utf8_encode` deprecation notice was printed inside the launcher JSON | `launcher/news.php` |
| D17 | Buying inactive items or items from another server was possible (no `active`/`server` check) | `buy.php` |
| D18 | Sidebar read sub-area names from `starloco_game.subarea_data.name` (no such column; names are in `starloco_login.world_base_sub_areas`); wanted-list `prepare()` received two arguments because of an unescaped quote | `include/rightmenu.php` (fixed in Phase 1) |
| D19 | Registration allowed `_` in account names, which the login server rejects (account unusable in game) | `AuthService` (fixed in Phase 3) |
| D20 | Login server `AccountData.update()` wrote the in-memory password back, reverting site password changes; SQL built by concatenation | StarLoco-Login (fixed in Phase 3) |
| D21 | Login server stored a missing nickname as the string `'null'` | StarLoco-Login `Account` (fixed in Phase 3) |
| D22 | Non-latin1 input compared with latin1 columns → "Illegal mix of collations" 500 | repositories (fixed in Phase 3, `Support\Text`) |

## Maintainability

- HTML, SQL and business logic interleaved in every page; the same queries duplicated
  (e.g. jobs list queried twice in `ladder.php`, drops queried twice per monster in `viewdrop.php`).
- Global variables (`$login`, `$jiva`, `$connection`) shared implicitly between includes.
- N+1 queries (shop templates, viewdrop, sidebar).
- Deprecated APIs: `utf8_encode`/`utf8_decode` (deprecated 8.2).
- No autoloading, no dependency management, no tests, no static analysis.
- Hard-coded third-party embeds (Facebook/Twitter pages of an old server, "Aestia").
