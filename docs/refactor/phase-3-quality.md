# Phase 3 — Quality, tests, password migration

## Tasks

- [ ] **Static analysis**: PHPStan level 6 (raise over time) on `src/`.
- [ ] **Code style**: php-cs-fixer, PSR-12.
- [ ] **Automated upgrades**: Rector (PHP 8.3 set) for any code left outside `src/`.
- [ ] **Unit tests (PHPUnit)**: `Experience`, `ShopService` (insufficient points, concurrent
      purchase, refund), `AuthService` (throttle, remember-me rotation), `Router` legacy redirects.
- [ ] **Integration tests**: repositories against a MariaDB service container seeded from
      `StarLoco-Game/db-init`.
- [ ] **CI**: lint + PHPStan + tests on every push.
- [ ] **Experience table sync**: `bin/sync-experience` regenerates `Experience` from
      `StarLoco-Game/scripts/data/Experience.lua`; CI fails if they differ.
- [ ] **Password hashing migration** (needs StarLoco-Login changes):
      1. Login server and portal accept both `SHA512(md5(pw))` and `password_hash()` (argon2id).
      2. On successful login, rehash to argon2id.
      3. After a grace period, force a reset for accounts still on the legacy hash.
- [ ] **Replace secret question/answer reset** with email reset links (token table, expiry),
      once outgoing mail is configured.
- [ ] **Clean-up of the theme**: remove old third-party embeds (Aestia Facebook/Twitter), hard-coded
      server ids/names (read from `world_servers`), Flash-only item previews.
