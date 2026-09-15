# JLoco-Web refactoring plan

Goal: turn the legacy portal (PHP 5-era, one file per page mixing HTML + SQL + logic) into a
secure, maintainable PHP 8 application with real URL paths (`/ladder` instead of `?page=ladder`)
and a Tailwind CSS UI (replacing Bootstrap 3 + jQuery), without a big-bang rewrite.

## Principles

- **Incremental (strangler pattern).** The site keeps working after every step. Pages are
  migrated one at a time; old and new code coexist during the transition.
- **URLs are a contract.** Old `?page=` links keep working (301 redirect) once real paths exist.
  The launcher endpoints (`launcher/status.php`, `launcher/news.php`, `launcher/files/`) and the
  Dofus client data (`lang/`) keep their exact URLs.
- **The login server owns the account format.** Hash formats change only together with JLoco-Login
  (Phase 3 added PBKDF2 to both; the default is still `SHA512(md5(password))`).
- **Light dependencies.** No full framework for 14 pages: Composer + a router + a template
  engine + dotenv; Tailwind (standalone CLI) and a few lines of plain JavaScript on the front end.
- **Restyle once.** The Tailwind migration happens when a page is rewritten as a template
  (Phase 2), not before, so no page is restyled twice.

## Phases

| Phase | File | Status |
|---|---|---|
| 0 — Security fixes, no restructuring | [phase-0-security.md](phase-0-security.md) | Done (2026-09-13) |
| 1 — Modern PHP foundation | [phase-1-foundation.md](phase-1-foundation.md) | Done (2026-09-13) |
| 2 — Layered architecture, real URL paths, Tailwind UI | [phase-2-architecture-routing.md](phase-2-architecture-routing.md) | Done (2026-09-13) |
| 3 — Tooling, tests, password migration | [phase-3-quality.md](phase-3-quality.md) | Done (2026-09-14); PBKDF2 still to switch on |

Findings that motivated the plan, with file references: [audit.md](audit.md).

## How to verify a step

The portal runs from `JLoco-Game/docker-compose.yml` (service `jloco_web`), with `JLoco-Web/`
bind-mounted at `/var/www/jloco-web` and `public/` served at `http://127.0.0.1/dofus/`. PHP and
template edits are live; CSS needs a rebuild.

```bash
cd JLoco-Game
docker compose build jloco_web jloco_web_assets    # only the web images (see Phase 1 note on jloco_login)
docker compose up -d jloco_web                        # runs jloco_web_migrate and jloco_web_assets first
docker compose run --rm jloco_web_assets              # rebuild public/assets/app.css after editing templates/CSS
docker compose run --rm jloco_web_migrate             # apply new migrations
docker compose logs -f jloco_web                      # PHP errors
docker compose run --rm jloco_web_tools              # composer check: cs, PHPStan, Rector, all tests
```
