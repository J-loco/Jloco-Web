# Phase 2 — Layered architecture, real URL paths, Tailwind UI

Scope: replace `?page=<name>` with real paths, split every page into controller / repository /
template, and rebuild the templates with Tailwind CSS instead of Bootstrap 3. Migrated page by page.

## Target layout

```
StarLoco-Web/
├─ public/                 ← Apache document root for /dofus (the only web-exposed folder)
│  ├─ index.php            ← front controller
│  ├─ .htaccess            ← rewrite everything that is not a file to index.php
│  ├─ css/ img/ plugins/   ← static assets (moved, same relative URLs)
│  ├─ lang/                ← Flash client data (URL must not change)
│  └─ launcher/            ← status.php, news.php, files/ (URLs must not change)
├─ src/
│  ├─ Http/Router.php      ← FastRoute wrapper, base-path aware (/dofus)
│  ├─ Controller/          ← HomeController, LadderController, ShopController, AccountController…
│  ├─ Repository/          ← AccountRepository, PlayerRepository, GuildRepository, ShopRepository,
│  │                         NewsRepository, DropRepository, ServerRepository  (all SQL lives here)
│  ├─ Service/             ← AuthService, ShopService (transactions), VoteService, Experience
│  └─ Security/            ← Csrf, Throttle, RememberMe (moved from include/ in Phase 0)
├─ templates/              ← Twig: layout.html.twig, partials/sidebar, pages/*.html.twig
├─ migrations/
├─ config/                 ← routes.php, services wiring
└─ docs/
```

Libraries: `nikic/fast-route`, `twig/twig` (auto-escaping on), `vlucas/phpdotenv`.

## Front-end: Tailwind instead of Bootstrap

The current theme is Bootstrap 3 + jQuery 1.11 + a dozen jQuery plugins (bxslider, jcarousel,
pace, notificationFx, style switcher). Since every page is rewritten as a Twig template in this
phase anyway, the UI is rebuilt with Tailwind at the same time rather than restyled twice.

- **Build**: Tailwind CSS v4 standalone CLI (no Node runtime needed in production). A Docker
  multi-stage build scans `templates/**/*.twig` and outputs `public/assets/app.css` (minified,
  only the classes used). Local work: `tailwindcss --watch` with the same standalone binary.
  No Play CDN in production (it compiles in the browser and conflicts with a strict CSP).
- **Design tokens** in `assets/css/app.css` (`@theme`): brand colors, fonts, radius, so the look
  is defined in one place; dark mode via `prefers-color-scheme` + a toggle.
- **Components** as Twig macros/partials (`templates/components/`): button, alert/flash, card,
  table, tabs, modal, form field, pagination, server-status badge. Pages compose these instead of
  repeating markup.
- **JavaScript**: replace jQuery + Bootstrap JS with Alpine.js (~15 KB) for the few interactive
  parts: login/register modals, ladder tabs, dropdown menu, flash auto-dismiss. Remove bxslider,
  jcarousel, pace, notificationFx, the style switcher and the Facebook/Twitter embeds.
- **Flash item previews** (`<object type="application/x-shockwave-flash">` in shop/ladder) do not
  render in any current browser: replace with static item/emblem images where available.
- **Accessibility**: real `<label for>`, focus styles, `aria-*` on tabs/modals, responsive tables.
- The Flash *client* data under `lang/` and `img/dofus/` is untouched (used by the game client).

Pages migrated to the new layout keep working next to legacy pages during the transition: the
legacy layout keeps Bootstrap until its last page is migrated (step 7), then `plugins/` and `css/`
are deleted.

## URL map

| Legacy | New path | Method |
|---|---|---|
| `?page=index`, `?num=N` | `/`, `/?p=N` | GET |
| `?page=join` | `/join` | GET |
| `?page=ladder` | `/ladder` (tabs: `/ladder/pvp`, `/ladder/guilds`, `/ladder/jobs/{jobId}`, `/ladder/votes`) | GET |
| `?page=shop&server=S&category=C` | `/shop`, `/shop/{server}`, `/shop/{server}/{category}` | GET |
| `?page=buy&template=T&server=S` | `/shop/{server}/items/{template}` (confirm) | GET / POST |
| `?page=viewdrop` | `/drops?monster=…` / `/drops?item=…` (GET search, shareable) | GET |
| `?page=vote` | `/vote` | GET / POST |
| `?page=signin` | `/login` | GET / POST |
| `?page=signin&ok=2` | `/logout` | POST |
| `?page=register` | `/register` | GET / POST |
| `?page=password` | `/password/reset` | GET / POST |
| `?page=profile` | `/account` | GET / POST |
| `?page=administration` | `/admin` | GET / POST |
| `?page=news` | `/forum-news` | GET |
| `?page=cgu` | `/terms` | GET |
| `launcher/status.php`, `launcher/news.php`, `lang/*`, `img/dofus/*` | unchanged | GET |

- **Legacy redirects**: any request with `?page=` gets a `301` to the new path (query parameters
  mapped as above), so bookmarks, forum links and search engines keep working.
- **Base path**: the app is served under `/dofus`; the router strips `parse_url(APP_URL, PHP_URL_PATH)`.
- **Assets**: templates reference assets with absolute URLs built from `APP_URL`
  (`asset('css/style.css')`), so nested paths like `/shop/601/3` do not break relative links.
- **Forms** use Post/Redirect/Get: POST handlers redirect with a flash message instead of rendering,
  so refresh never re-submits (fixes the "profile toggle flips twice" class of bugs).

## Migration order (one PR each)

1. Router + `public/` + legacy redirect + Tailwind build + new layout and components. Pages not
   migrated yet are still rendered by the old `pages/*.php` inside the legacy Bootstrap layout,
   through a `LegacyPageController`. **Real paths work from this step on.**
2. Read-only pages: home, join, ladder, drops, forum-news, terms.
3. Auth: login, logout, register, password reset (AuthService).
4. Account (profile) and vote.
5. Shop and buy (ShopService, transactions).
6. Admin.
7. Delete `pages/`, `include/`, `class/`, `configuration/`, the `url()` shim and `?page=` handling
   (keep only the 301 redirect).

## Done when

- No PHP file outside `public/index.php` and `public/launcher/*.php` is reachable over HTTP.
- No SQL outside `src/Repository/`; no `echo` of HTML outside `templates/`.
- Every legacy URL in the table returns a 301 to its new path.
- No Bootstrap, jQuery or `plugins/` left; CSS comes from the Tailwind build only.
