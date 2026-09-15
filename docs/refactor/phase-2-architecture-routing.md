# Phase 2 — Layered architecture, real URL paths, Tailwind UI

**Status: done (2026-09-13).** The legacy `?page=` portal is gone; every page is a controller +
repository + Twig template, styled with Tailwind, served from `public/` at real paths.

## Layout

```
JLoco-Web/
├─ public/                   ← the only web-served folder (Apache Alias /dofus)
│  ├─ index.php              ← front controller (FallbackResource)
│  ├─ assets/                ← app.css (built, git-ignored), app.js, img/, fonts/
│  ├─ lang/                  ← Dofus client data: URL contract, untouched
│  └─ launcher/              ← status.php, news.php, manifest.json, files/: URL contract (docs/launcher-endpoints.md)
├─ config/
│  ├─ autoload.php           ← Composer + src/ + optional .env
│  ├─ container.php          ← service wiring (everything else is autowired)
│  └─ routes.php             ← every route, by name
├─ src/
│  ├─ Kernel.php             ← legacy redirect → session → remember-me → routing → CSRF → controller; errors; security headers
│  ├─ Container.php          ← shared instances + constructor autowiring
│  ├─ Http/                  ← Request, Response, Route, Router (FastRoute + URL generation), LegacyUrls (301s)
│  ├─ Controller/            ← Home, Page, Ladder, Drop, Auth, Account, Vote, Shop, Admin
│  ├─ Repository/            ← Account, Player, Guild, News, Shop, Server, Game (all page SQL)
│  ├─ Service/               ← AuthService, ShopService, VoteService, ServerStatus, Dedipass, ForumFeed, Sidebar
│  ├─ Security/              ← Session (+ flash), Csrf, Throttle, RememberMe
│  ├─ Game/                  ← Experience, Character (breeds, alignments), ItemEffects
│  ├─ View/                  ← View, TwigFactory, AppExtension (url, asset, csrf_field, filters)
│  ├─ Support/Text.php       ← charset guards for the latin1 / utf8mb3 legacy schemas
│  ├─ Migration/             ← Migrator, WebUserProvisioner (bin/migrate)
│  ├─ Config.php, Database.php, Captcha.php
├─ templates/                ← layout, layout_narrow, partials/, components/ui.html.twig, pages/, errors/
├─ assets/css/app.css        ← Tailwind source (design tokens + components)
├─ migrations/, bin/migrate, docker/, Dockerfile
└─ docs/
```

Libraries: `nikic/fast-route`, `twig/twig` (auto-escaping), `vlucas/phpdotenv`. SQL lives in
`src/Repository/`, plus the two security stores (`Security/Throttle`, `Security/RememberMe`) and the
migration runner.

## URL map (implemented)

| Legacy | New path | Method |
|---|---|---|
| `?page=index`, `?num=N` | `/`, `/?p=N` | GET |
| `?page=join` | `/join` | GET |
| `?page=cgu` (never existed) | `/terms` | GET |
| `?page=news` | `/forum-news` | GET |
| `?page=ladder` | `/ladder`, `/ladder/pvp`, `/ladder/guilds`, `/ladder/jobs`, `/ladder/jobs/{job}`, `/ladder/votes` | GET |
| `?page=viewdrop` | `/drops?by=monster\|item&q=…` | GET |
| `?page=signin` | `/login` | GET / POST |
| `?page=signin&ok=2` | `/logout` | POST only |
| `?page=register` | `/register` (+ `/captcha.png`) | GET / POST |
| `?page=password` | `/password/reset` | GET / POST |
| `?page=profile` | `/account`, `/account/privacy/{armory\|position}`, `/account/password` (POST); Dedipass posts to `/account` | GET / POST |
| `?page=vote` | `/vote` | GET / POST |
| `?page=shop&server=S&category=C` | `/shop`, `/shop/{server}`, `/shop/{server}/{category}` | GET |
| `?page=buy&template=T&server=S` | `/shop/{server}/items/{template}` | GET / POST |
| `?page=administration` | `/admin`, `/admin/news`, `/admin/news/{id}/delete`, `/admin/game-news`, `/admin/game-news/{id}/delete` | GET / POST |
| `lang/*`, `launcher/*` | unchanged | GET |

Every legacy URL above answers `301` to its new path (`src/Http/LegacyUrls.php`); unknown pages 404.

## Front-end

- **Tailwind v4.3** standalone CLI, no Node: the `jloco_web_assets` compose service (Dockerfile
  target `tailwind`) builds `public/assets/app.css`; the `production` target builds it into the image.
  Tokens (brand amber palette, Bebas Neue display font) and components (`.card`, `.btn-*`, `.input`,
  `.table`, `.tabs`, `.badge`…) are in `assets/css/app.css`; Twig macros in `templates/components/ui.html.twig`.
- **No Alpine.js** (deviation from the first plan): the standard Alpine build evaluates expressions with
  `new Function`, which would force `'unsafe-eval'` into the CSP. Native `<dialog>` (login),
  `<details>` (menus) and ~70 lines of `public/assets/app.js` (theme, background, dialogs, dismiss,
  captcha refresh, delete confirmation) keep `script-src 'self' 'nonce-…'`. Everything works without JS.
- **Images of the original theme kept** in `public/assets/img/`: page background patterns (default
  `shattered`, `dark_geometric` in dark mode, the old style switcher's choices in the "Apparence" menu,
  saved per browser), server icons in the sidebar, the RPG Paradize banner as the vote button, the
  default avatar on the account page, Bebas Neue for titles. `slideshow/1-4.jpg` are blank placeholders
  (the old slideshow was commented out) and are not displayed.
- Dropped: Bootstrap 3, jQuery and its plugins, Facebook/Twitter embeds of a former server, Flash item and
  guild-emblem previews (no browser runs Flash; `img/dofus/` never existed in this repo).

## Security and behaviour changes

- Post/Redirect/Get everywhere; flash messages survive the redirect.
- CSRF token on every POST route except the Dedipass callback; logout is POST-only.
- Account names (login credentials) are never displayed: the vote ranking shows pseudos.
- Registration: username `[A-Za-z0-9_-]{3,30}`, password 6–50, question/answer 2–100 latin1 characters,
  captcha always consumed; errors shown per field.
- Non-admins get a 404 on `/admin`.
- Headers on every response: CSP with per-request nonce, `X-Frame-Options`, `X-Content-Type-Options`,
  `Referrer-Policy`.
- Input charset hardening (`Support/Text`): invalid UTF-8 is scrubbed; values latin1 cannot represent
  never reach latin1 comparisons (they used to raise "Illegal mix of collations" → 500).

## Docker

- Apache serves only `public/` (`docker/apache.conf`: `Alias ${APP_BASE_PATH}`, `FallbackResource`);
  `/` redirects to `/dofus/`.
- `runtime` target (compose, code bind-mounted at `/var/www/jloco-web`), `tailwind` target (asset
  build), `production` target (code + CSS baked in, `opcache.validate_timestamps=0`,
  `public/launcher/files` excluded: mount it). Verified: production image serves pages, CSS, `lang/`,
  launcher endpoints; source files answer 404.

## Verification (2026-09-13)

- Every route rendered through the Kernel with `strict_variables` as guest and as admin: no error.
- HTTP end to end: registration (field errors, captcha case-insensitive), CSRF rejection, login
  (wrong password, remember-me cookie, restore without session), privacy toggle (unknown flag 404),
  password change (wrong then right answer), vote cooldown (atomic, second vote refused), shop (forced
  purchase without points refused; purchase debits once, writes the gift and the log), admin news
  create/list/delete for website and in-game news, non-admin denied, GET logout refused, logout.
- Legacy redirects, launcher JSON, `lang/`, captcha PNG, hostile inputs (invalid bytes, CJK, emoji).
- Screenshots (desktop + mobile) of home, ladder, register, drops.

## Deviations from the first version of this plan

- No `LegacyPageController` bridge: all pages were migrated in one pass, so the site never ran half-legacy.
- No Alpine.js (see Front-end).
