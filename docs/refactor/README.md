# StarLoco-Web refactoring plan

Goal: turn the legacy portal (PHP 5-era, one file per page mixing HTML + SQL + logic) into a
secure, maintainable PHP 8 application with real URL paths (`/ladder` instead of `?page=ladder`)
and a Tailwind CSS UI (replacing Bootstrap 3 + jQuery), without a big-bang rewrite.

## Principles

- **Incremental (strangler pattern).** The site keeps working after every step. Pages are
  migrated one at a time; old and new code coexist during the transition.
- **URLs are a contract.** Old `?page=` links keep working (301 redirect) once real paths exist.
  The launcher endpoints (`launcher/status.php`, `launcher/news.php`) and the Flash client data
  (`lang/`, `img/dofus/`) keep their exact URLs.
- **The login server owns the account format.** Passwords stay `SHA512(md5(password))` until
  StarLoco-Login is changed too (tracked in Phase 3).
- **Light dependencies.** No full framework for 14 pages: Composer + a router + a template
  engine + dotenv; Tailwind (standalone CLI) + Alpine.js on the front end.
- **Restyle once.** The Tailwind migration happens when a page is rewritten as a template
  (Phase 2), not before, so no page is restyled twice.

## Phases

| Phase | File | Status |
|---|---|---|
| 0 — Security fixes, no restructuring | [phase-0-security.md](phase-0-security.md) | Done (2026-09-13) |
| 1 — Modern PHP foundation | [phase-1-foundation.md](phase-1-foundation.md) | Done (2026-09-13) |
| 2 — Layered architecture, real URL paths, Tailwind UI | [phase-2-architecture-routing.md](phase-2-architecture-routing.md) | Planned |
| 3 — Tooling, tests, password migration | [phase-3-quality.md](phase-3-quality.md) | Planned |

Findings that motivated the plan, with file references: [audit.md](audit.md).

## How to verify a step

The portal runs from `StarLoco-Game/docker-compose.yml` (service `starloco_web`), with
`StarLoco-Web/` bind-mounted at `/var/www/html/dofus`, so PHP edits are live without a rebuild.

```bash
cd StarLoco-Game
# Lint every PHP file
docker compose exec -T starloco_web sh -c 'cd /var/www/html/dofus && find . -name "*.php" -not -path "./plugins/*" -exec php -l {} \; | grep -v "No syntax errors"'
# Smoke test: every page must render without "Warning:", "Fatal error:" or "Deprecated:"
curl -s http://127.0.0.1/dofus/ | grep -E "(Warning|Fatal error|Deprecated):"
```

Portal DB migrations live in `StarLoco-Web/migrations/` and are applied by the one-off
`starloco_web_migrate` service before `starloco_web` starts (details in Phase 1):

```bash
docker compose build starloco_web && docker compose up -d starloco_web
docker compose run --rm starloco_web_migrate   # apply new migrations without restarting
docker compose logs -f starloco_web            # PHP errors are logged here
```
