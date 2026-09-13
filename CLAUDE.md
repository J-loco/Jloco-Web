# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Overview

StarLoco is a Dofus 1.39 private-server emulator split into four sub-projects:

| Sub-project | Language | Role |
|---|---|---|
| `StarLoco-Game` | Java 21 (Gradle) | Game server — core gameplay, fights, world state |
| `StarLoco-Login` | Java 8 (Gradle) | Authentication server — account login, server list |
| `StarLoco-Client` | Electron (JS) | Patched Dofus 1.39.8 desktop client |
| `StarLoco-Web` | PHP | Web portal — registration, shop, ladder |

## Build & Run

### Game server (Java 21 + Amazon Corretto)
```bash
cd StarLoco-Game
./gradlew jar          # produces build/libs/game.jar
./build.sh             # gradle jar + copies game.jar to project root
java -jar game.jar     # or start.bat on Windows
```
Config: `game.config.properties` (or via `STARLOCO_CONFIG_PATH` env var)

### Login server (Java 8)
```bash
cd StarLoco-Login
./gradlew jar          # produces build/libs/login.jar
java -jar login.jar    # or start.bat on Windows
```
Config: `login.config.properties`

Database setup: create `starloco_login` DB and run `login.sql`. Game DB: create `starloco_game` and run `game.sql`.

### Docker (full stack)
```bash
cd StarLoco-Game
docker compose -f docker-compose.yml up
```
This spins up MariaDB, Redis, the login image, and builds+runs the game image. Config overrides live in `StarLoco-Game/config/`.

## Architecture

### Packet protocol
Both servers use Apache MINA with a newline+NUL text codec. Packets are 2-character header strings followed by payload (e.g. `"AT"`, `"GJ"`). The Game server routes packets two ways:
1. **New-style** (`DofusMessageFactory` + `EventDispatcherFactory`): classes annotated with `@DofusMessage(header="XX")` are discovered via reflection; dispatched through `AbstractEventMessageDispatcher` subclasses annotated with `@Handler`.
2. **Legacy** (`client.parsePacket()`): a large switch/dispatch in `GameClient`.

### Game server internals (`StarLoco-Game/src/org/starloco/locos/`)
- **`kernel/`** — `Main` (entry point, main loop), `Config` (all config properties), `Logging`
- **`game/`** — `GameServer` (MINA acceptor), `GameHandler` (session lifecycle), `GameClient` (per-connection state + legacy packet dispatch), `game/world/World` (singleton world state: players, maps, NPCs, guilds)
- **`database/`** — `DatabaseManager` manages two HikariCP pools (login DB + game DB). Each entity type has a `DAO<T>` subclass under `database/data/game/` or `database/data/login/`. DAOs are registered and retrieved via `DatabaseManager.get(SomeData.class)`.
- **`script/`** — Lua scripting via the `classdump/luna` JVM-Lua runtime. `ScriptVM` is the base; `DataScriptVM` (singleton) loads static game data (NPCs, maps, XP tables, admin commands, animations) from `scripts/Data.lua` + subdirectories. Scripts can be plain directories or `.zip` archives.
- **`fight/`** — `Fight`, `Fighter` hierarchy (`PlayerFighter`, `MobFighter`, `SummonFighter`, etc.), spell system, IA (monster AI), traps, turns.
- **`entity/`** — monsters, NPCs, mounts, pets, collectors, prisms.
- **`area/`** — `GameMap`, `GameCase` (cells), pathfinding, sub-areas.
- **`exchange/`** — `ExchangeClient` connects game server to login server over a private TCP channel on port 666 (key-authenticated). The login server runs a matching `ExchangeServer`.

### Login server internals (`StarLoco-Login/src/org/starloco/locos/`)
- **`login/`** — `LoginServer` accepts client connections; `LoginHandler`/packet classes handle authentication flow (version check → account name → password → server selection).
- **`exchange/`** — `ExchangeServer` listens for game-server connections; `PacketHandler` routes inter-server messages (server state updates, player counts).
- **`database/`** — single MariaDB connection (`Database`), DAOs for accounts, players, servers.

### Lua scripts (`StarLoco-Game/scripts/`)
- `Common.lua` — shared utilities, loaded first by every VM.
- `Data.lua` — entry point for static data; uses `LoadPack()` to load subdirectories (`data/`, `models/`, `eventhandlers/`).
- `Java.lua` — Java interop helpers exposed to scripts.
- `data/` — NPC definitions, map definitions, experience tables, admin commands/groups, animations, dungeons, etc.
- `eventhandlers/` — Lua-side event handlers registered via `Handlers` (the `EventHandlers` object injected into the VM).
- `models/` — reusable Lua model definitions.

### Web portal (`StarLoco-Web/`)
PHP 8.3 app (Composer: FastRoute, Twig, phpdotenv) served from `public/` at `http://127.0.0.1/dofus/`. Request flow: `public/index.php` → `src/Kernel.php` (legacy `?page=` 301 redirects, session, remember-me, routing, CSRF, error pages, CSP/security headers) → controller in `src/Controller/` → repositories (`src/Repository/`, all page SQL) and services (`src/Service/`) → Twig template (`templates/`). Routes are named in `config/routes.php` (`url('name', params)` in Twig and controllers); services are autowired by `src/Container.php` (factories in `config/container.php`). Settings: `src/Config.php`, from env (documented in `StarLoco-Web/.env.example`; compose passes `WEB_*` variables from `StarLoco-Game/.env`).

URL contracts that must not move: `public/lang/` (Dofus client data server) and `public/launcher/` (`status.php`, `news.php`, `manifest.json`, `files/`, see `StarLoco-Web/docs/launcher-endpoints.md`).

Docker (from `StarLoco-Game/`): `docker compose build starloco_web starloco_web_assets && docker compose up -d starloco_web`. Build only the web images: `starloco_login` currently also has `build: ../StarLoco-Web`. One-off services run first: `starloco_web_migrate` (root; `bin/migrate` applies `StarLoco-Web/migrations/*.sql` and provisions the least-privilege `starloco_web` DB user, grants in `src/Migration/WebUserProvisioner.php`) and `starloco_web_assets` (Tailwind standalone CLI → `public/assets/app.css`, git-ignored; re-run after editing templates or `assets/css/app.css`). PHP errors: `docker compose logs starloco_web`. Dockerfile targets: `runtime` (bind-mounted code), `tailwind`, `production` (self-contained).

Rules for portal code (plan and history in `StarLoco-Web/docs/refactor/`; Phases 0-2 done, Phase 3 planned):
- SQL only in repositories (plus `Security/Throttle`, `Security/RememberMe`), always with `?` placeholders. Prepared statements are native: bad SQL fails at `prepare()`.
- Legacy schemas: login-DB text columns are latin1, game names utf8mb3. Check user input with `Support\Text::fitsLatin1()` before storing or comparing it against latin1 columns (otherwise MariaDB throws "Illegal mix of collations" → 500). Request input is already UTF-8-scrubbed.
- Controllers return a `Response`; POST actions end with `flash()` + redirect (Post/Redirect/Get). Every POST route needs `{{ csrf_field() }}` in its form unless declared `csrf: false` in `config/routes.php` (only the Dedipass callback).
- Templates auto-escape; `|raw` only for admin-authored news content. Never display `world_accounts.account` (login credential): use `pseudo`.
- Inline scripts need `nonce="{{ csp_nonce }}"`; no JS framework (the CSP forbids `unsafe-eval`). Interactivity lives in `public/assets/app.js`.
- Styles: Tailwind utilities + components in `assets/css/app.css`; UI macros in `templates/components/ui.html.twig`. Theme images (backgrounds, server icons, vote banner, avatar) are in `public/assets/img/`.
- XP progress comes from `src/Game/Experience.php` (mirror of `StarLoco-Game/scripts/data/Experience.lua`; the `experience` SQL table no longer exists).
- Schema changes: add a re-runnable `StarLoco-Web/migrations/NNN_name.sql` (first line `-- database: game` to target the game DB), never `StarLoco-Game/db-init/`.

### Client SWF mods (`StarLoco-Client/`)
The Dofus 1.39 client is shipped as an Electron app wrapping a Flash runtime. The main script SWF lives at `StarLoco-Client/resources/app/retroclient/loader.swf`. Source is AS2 with heavy obfuscation (classes renamed to `_SafeStr_NNN`, members bracket-accessed with non-printable string keys like `this.api["\x1c\x16\n"]`).

**Editing workflow — use JPEXS Free Flash Decompiler, P-Code tab (not decompiled AS):**
1. Open `loader.swf` in JPEXS.
2. Navigate the script tree to the target class (e.g. `dofus._SafeStr_0.gapi.ui.StatsJob`).
3. Click the **P-Code** tab and edit instruction-by-instruction. Preserve labels that are jump targets (`locXXXX:`) — they're referenced by `If`/`Jump` offsets elsewhere in the function.
4. Click the in-pane **Save** to commit the P-Code change, then **File → Save** to write the SWF.

Why not edit the decompiled ActionScript: JPEXS' AS2 recompiler is sometimes lossy with obfuscated string-keyed member accesses and can break the runtime lookups. P-Code edits preserve the constant pool verbatim.

Stat-boost client/server contract (relevant when modding `StatsJob`): the client sends `AS<stat>` (single +1) or `AS<stat>|<quantity>` (multi). Both are handled in `StarLoco-Game/src/org/starloco/locos/game/GameClient.java#boost` (`Player.boostStat` / `Player.boostStatFixedCount`).

**Applied patch — StatsJob "always show quantity popup" (2026-05-21):**
In `dofus._SafeStr_0.gapi.ui.StatsJob`, function `click`, inside the `_btn10`–`_btn15` handler block, label `loc1277` originally held:
```
loc1277:Not
If loc13cc
```
This skipped the popup when no modifier key was held. Replace with:
```
loc1277:Pop
```
The `Pop` discards the boolean and falls through unconditionally to the popup construction, making every click open the quantity dialog. The label `loc1277` must be kept — it is a jump target from `loc1423` elsewhere in the function.

### Drop system (`StarLoco-Game/`)

The `drops` table columns: `objectId`, `monsterId`, `percentGrade1`–`percentGrade5`, `minObj`, `maxObj`, `condition`, `action`.

**`action` semantics:**
- `'-1'` — regular drop → loaded into `dropsPlayers` → per-winner RNG roll at end of fight
- `'1'` — meat drop → loaded into `dropsMeats` → only delivered if the winning player's equipped weapon has spell effect `795` (Chasseur / Hunter job trait); otherwise silently discarded

**Drop pipeline in `Fight.java`:**
- `4595–4628` — splits dead mob's drops into `dropsPlayers` (action != 1) and `dropsMeats` (action == 1)
- `4856–4976` — per-winner roll: `chance = localPercent × prospecting × conquestBonus × challengeFactor × starFactor × Config.rateDrop`
- `4978–4998` — meat path: delivers to inventory only if weapon effect 795 is equipped
- `5040–5092` — builds `dropsToAttribute`, then `TimerWaiter.addNext` gives items after 1 second

**`DropData.loadFully()` (`database/data/game/DropData.java`):** Clears all monster drops, then re-attaches from DB. Silently skips any row where `getObjTemplate(objectId) == null` OR `getMonstre(monsterId) == null`. Check server logs for "loaded successfully" to confirm.

**In-game admin command:** `.RELOAD DROPS` — reloads the drops table from DB into memory without a server restart.

**Meat items are NOT a single item type.** For example, `Chair de Larve` is type 63 but `Cervelle de larve` is type 69. Never use a single `item_template.type` value to distinguish meat from regular drops when writing SQL fixes.

**Diagnostic query:**
```sql
SELECT objectId, action FROM drops WHERE monsterId = <id>;
```
If all rows show `action = 1`, the drop clobber bug (see DB Migrations below) is active.

**Live DB repair (if migration 08 ran with bad data):**
```bash
# In StarLoco-Game directory:
sed -n '3474,8072p' db-init/04-game.sql > /tmp/10-update_game_drops_database_22.05.26.sql
# Then in mysql:
# USE starloco_game; TRUNCATE TABLE drops; SOURCE /tmp/10-update_game_drops_database_22.05.26.sql;
```
Then run `.RELOAD DROPS` in-game.

### DB migrations (`StarLoco-Game/db-init/`)

Docker init runs SQL files in numeric order: `04-game.sql` (full seed) → `05` → `06` → `07` → `08-update_game_08.05.23.sql`.

**Known fixed bug (2026-05-21):** `08-update_game_08.05.23.sql` originally DROPped and recreated the `drops` table with every row set to `action = '1'`, clobbering the correct values from `04-game.sql`. Symptom: players receive XP and kamas after fights but zero item drops. The file was rewritten — its first 78 lines (USE / sorts UPDATE / donjons) are preserved; the drops section was replaced with `04-game.sql:3474-8072` (DROP/CREATE + correct INSERTs with proper action values). Future `docker compose up` deploys will produce correct drops automatically.

## Key Configuration (`game.config.properties`)
```
system.server.exchange.ip / .port / .key  — game↔login channel
system.server.game.ip / .port / .id       — public game socket
database.login.*  /  database.game.*      — DB credentials
system.server.game.rate.*                 — XP/drop/kamas multipliers
system.server.game.mode.heroic            — heroic server mode
system.server.debug                       — enables debug log level
```
