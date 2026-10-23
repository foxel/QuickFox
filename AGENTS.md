# QuickFox — Agent Notes

Legacy PHP CMS/forum (LION 2006–2007, 2026). No package manager, tests, or CI. `php -l` works if PHP is installed.

## Runtime

- **Entry point:** `index.php`
- **Routing:**
  - `?st=page` → `includes/pages/{page}.inc.php` (`main` if missing)
  - `?sr=job` → `includes/jobs/{job}.job.php` (alt output: CSS, JS, AJAX, download, RSS, etc.)
  - `?script=...` → handled by scripts in `includes/scripts/`
- **Encoding:** `windows-1251` / `cp1251`. Headers and DB connection use it. Source files are not UTF-8.
- **PHP style:** `<?php` tags, `__construct()` constructors, globals-heavy. PHP 7.0+ compatible; runs on PHP 8.2 after the 2026 migration.

## Setup / install

1. Build distributable: `./.pack.sh` → creates `pack.tgz` from `git archive HEAD` (honors `.gitattributes`).
2. Serve repo root and open `setup.php` in a browser.
3. `setup.php` extracts `pack.tgz`, runs `install/install.php`, writes `QF_config.php`.
4. Provide MySQL 4+ credentials; installer creates/updates DB structure and admin account.
5. On finish, `setup.php` deletes itself (unless `setup.lock` exists).

`QF_config.php` is generated and **not** tracked. It holds `$QF_Dbase_Config` (host, DB, user, pass, table prefix).

## Architecture

- `kernel/init.php` — bootstraps DB (`db/mysql4.php`), user/session, parser, loads config from DB.
- `kernel/start.php` — GZIP, style/visual load, ban/lock handling.
- `kernel/core_*.php` — core modules (users, sessions, parser, email, files, SQL abstractions).
- `kernel/visualizer.php` + `visuals/{style}/*.vis` — template system.
- `styles/{style}/*.ecss` — style CSS.
- `langs/{lang}/` — language packs (default `ru`).
- `includes/pages/`, `includes/jobs/`, `includes/scripts/`, `includes/admin/`, `includes/ajax/`, `includes/panels/` — request handlers.

## Writable directories

`avatars/`, `files/`, `cache/` must be writable by the web server. They contain only `.exists` in repo.

## Editing caveats

- Do **not** convert files to UTF-8; keep `windows-1251`.
- Several PHP files contain literal `windows-1251` bytes (Cyrillic strings, etc.). If an editor or tool reads them as UTF-8, invalid bytes are replaced with U+FFFD and the file is silently corrupted on save. Verify the high-byte stream after any edit, or edit in binary/encoding-aware mode.
- Keep PHP 7.0+ compatible syntax; the code was migrated from short tags and old-style constructors in 2026.
- `.gitattributes` marks `setup.php`, `.pack.sh`, `Archive.php`, `.gitignore`, `.gitattributes` as `export-ignore` so they are omitted from `pack.tgz`.
- Minimal verification: `php -l <file>` if PHP is available (do not install PHP just for this).
- Otherwise smoke-test by loading `index.php` or the relevant `?st=` / `?sr=` route in a local PHP+MySQL environment.
