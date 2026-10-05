# AGENTS.md — Salisberg

Guidance for any agent or developer working in this repository. Read it before changing anything, and update the change log at the bottom when you finish.

## 1. What this project is

- **Product name:** Salisberg. **Production URL:** https://salisberg.com
- **Base:** a fork of [QloApps](https://github.com/Qloapps/QloApps) (Webkul), an open-source hotel booking engine that is itself built on PrestaShop 1.6. License: OSL-3.0.
- **Stack:** PHP 8.1 + Apache, MySQL 8.0, Smarty templates. No Composer dependencies at the root, no Node build step.
- **Hosting:** a VPS running [Coolify](https://coolify.io). Coolify builds `docker-compose.yml` from this repo and its own proxy terminates TLS for the domain.
- The repo was briefly named "StayFlow" (see `README.md`); the internal code, database prefix (`qlo_`) and many file names still say QloApps. That is expected.

## 2. Rules

### Docker and deployment

1. **Never mount a volume over `/var/www/html` (the code).** Code ships in the image. Only the four data paths are volumes: `/data`, `img`, `upload`, `download`. Mounting over the code is exactly what breaks the upstream image (see section 4).
2. **Do not go back to `webkul/qloapps_docker`.** It is an all-in-one image (Apache + MySQL + SSH in one container) and is the source of the known Docker problems.
3. **`docker-compose.yml` must stay Coolify-safe:** no `ports:`, no `container_name:`, no bind mounts to host paths, no custom reverse proxy. Host ports belong only in `docker-compose.override.yml`, which is for local use.
4. **No secrets in git.** `.env` is ignored. Production values live in Coolify's Environment Variables tab. `.env.example` documents every variable and holds placeholders only.
5. **Required variables use `${VAR:?}`** in compose so a missing secret fails the deploy instead of starting with an empty password.
6. **Shell scripts must have LF line endings.** `.gitattributes` enforces it and the Dockerfile strips CRLF as a second guard. A CRLF entrypoint fails with `exec format error` / `no such file or directory`.
7. **The entrypoint must stay idempotent.** It runs on every container start and every redeploy. Anything it does must be safe to repeat.
8. **Never delete or recreate the `db_data` or `app_data` volumes on production** without a verified backup. `app_data` holds `settings.inc.php`, which contains the cookie/encryption keys; losing it invalidates every stored password.
9. **`AUTO_INSTALL` runs with `--db_clear=1`.** It only triggers when `/data/settings.inc.php` is absent. Never remove that file while pointing at a database you want to keep.
10. **`ADMIN_DIR` must not be `admin`.** The back office refuses that name and renames the folder randomly, which would change the admin URL on every deploy.

### Code

11. **Do not edit core files when an override will do.** Use `override/` and modules in `modules/`, as PrestaShop 1.6 intends. Core edits make upstream QloApps updates painful.
12. **Do not rename the `qlo_` table prefix or internal `QloApps`/`PrestaShop` identifiers** as part of branding work. Branding is done through shop name, theme, logos and translations.
13. **Match the surrounding code style** (PrestaShop 1.6 conventions: PSR-2-ish PHP, Smarty `.tpl`).
14. **Keep the upstream license headers** in files you touch (OSL-3.0 requirement).

### Process

15. **Verify before claiming.** Build the image and boot the stack locally (section 5) after any change to `Dockerfile`, `docker/` or compose files. Record what was and was not tested.
16. **Commit or push only when the owner asks.** Work on a branch, not directly on `main`.
17. **Record every meaningful change in section 6** with what, why and how.
18. **Track upstream by tagged release only.** Never merge `Qloapps/QloApps` `develop`. When a new tag ships, diff it against the current base tag, review, and re-test the Docker boot before deploying.
19. **Files under upstream-ignored paths** (`img/**`, `modules/*/translations/*`) need `git add -f`. Check for silently dropped files after any import from upstream.

## 3. How the Docker setup works

| File | Purpose |
|---|---|
| `Dockerfile` | `php:8.1-apache` plus the extensions QloApps requires (gd, pdo_mysql, mysqli, soap, zip, intl, mbstring, opcache). Copies the repo into `/var/www/html` and keeps a pristine copy of `img`, `upload`, `download` in `/usr/src/salisberg-seed`. |
| `docker/entrypoint.sh` | Runs at every start: seeds and fixes ownership of volumes, renames `admin` to `$ADMIN_DIR`, waits for MySQL, runs the CLI installer on first boot, persists settings, removes `install/`. |
| `docker/php.ini` | Limits from the QloApps requirements (memory, upload size, execution time) and opcache. |
| `docker/apache.conf` | `AllowOverride All` for `.htaccess` rewrites; trusts `X-Forwarded-For` from the proxy. |
| `docker-compose.yml` | `app` + `db` services and named volumes. This is the file Coolify deploys. |
| `docker-compose.override.yml` | Local only. Publishes the app on `HTTP_PORT` (default 8080). |
| `.env.example` | Every variable, with placeholders. |

**State and where it lives**

| Volume | Mounted at | Contains |
|---|---|---|
| `db_data` | `/var/lib/mysql` | The database |
| `app_data` | `/data` | `settings.inc.php` (DB credentials, encryption keys), symlinked into `config/` |
| `app_img` | `/var/www/html/img` | Hotel, room and logo images |
| `app_upload`, `app_download` | `/var/www/html/upload`, `/download` | Customer and admin uploads |

Everything else in the container is disposable and rebuilt from git on each deploy. Consequence: modules or themes installed through the back office UI do **not** survive a redeploy. Add them to the repo instead.

**HTTPS** is terminated by Coolify's proxy. The app detects it through `X-Forwarded-Proto` (`Tools::usingSecureMode()`), and the entrypoint turns on `PS_SSL_ENABLED` at install time when `PUBLIC_URL` starts with `https://`.

## 4. The upstream Docker problem and how it is avoided

Reference: [coollabsio/coolify#4058](https://github.com/coollabsio/coolify/issues/4058).

- **Symptom:** "Forbidden: You don't have permission to access this resource" on the install page when the official image is run with a persistent volume.
- **Cause:** the official image keeps the application code inside the path that gets mounted (`/home/qloapps/www`). A fresh volume arrives root-owned (or empty), so Apache cannot read the code. The reported workaround was a manual `chmod -R 755` on the volume.
- **Fix here, by design rather than by workaround:**
  1. Code is never on a volume, so a volume can never hide or lock it.
  2. Volumes cover data directories only.
  3. On every start the entrypoint, running as root, copies seed content into any empty data volume and `chown`s the data paths to `www-data` before Apache starts.
- Other faults of the upstream image that this setup removes: MySQL and SSH published to the internet, MySQL and Apache sharing one container, no health checks, and no link between the image and this repo's code.

## 5. Running and deploying

### Local

```bash
cp .env.example .env        # then set real passwords
docker compose up -d --build
docker compose logs -f app  # first boot runs the installer; allow a few minutes
```

- Site: `http://localhost:8080`
- Back office: `http://localhost:8080/admin-salisberg` (login: `ADMIN_EMAIL` / `ADMIN_PASSWORD`)
- Full reset (destroys all data): `docker compose down -v`

### Production (Coolify)

1. Point DNS `A` records for `salisberg.com` and `www.salisberg.com` at the VPS.
2. In Coolify: New Resource → the Git repository → Build Pack **Docker Compose** → compose file `/docker-compose.yml`.
3. Assign the domain `https://salisberg.com` to the **app** service (container port 80).
4. Add the variables from `.env.example` in the Environment Variables tab. Set `PUBLIC_URL=https://salisberg.com`, strong unique passwords, and a non-default `ADMIN_DIR`. Leave `HTTP_PORT` out.
5. Deploy. Watch the `app` logs for `Installation complete.`
6. After first login, change the admin password in the back office and set up scheduled database backups in Coolify.

`PUBLIC_URL` is applied on every start (see the change log), so changing the domain is a variable change plus a redeploy.

## 6. Change log

### 2026-10-05 — Replace Docker setup, target Coolify

**What**

- Replaced `docker-compose.yml`, which only pulled `webkul/qloapps_docker:latest`, with a two-service stack (`app`, `db`) built from this repo.
- Added `Dockerfile`, `docker/entrypoint.sh`, `docker/php.ini`, `docker/apache.conf`, `docker-compose.override.yml`, `.dockerignore`, `.env.example`, `.gitattributes`, and this file.
- Named the stack, database, default shop name and admin folder for Salisberg.

**Why**

- The upstream image fails with a Forbidden error when given persistent volumes (section 4), and it never deployed this repo's code at all: it ran whatever was baked into Webkul's image.
- It also exposed MySQL (3306) and SSH (2222) on the host.
- Coolify needs a compose file with no published ports and no fixed container names.

**How / methods**

- Read the installer (`install/index_cli.php`, `install/classes/datas.php`) to drive a non-interactive first-boot install from environment variables instead of the web wizard.
- Read `Tools::usingSecureMode()` to confirm `X-Forwarded-Proto` is honoured, so no extra HTTPS shim is needed behind the proxy.
- Read `AdminLoginController` to find the forced rename of the `admin` folder, and pinned the name through `ADMIN_DIR`.
- Kept `settings.inc.php` on a volume through a symlink, so redeploys keep the install without putting the whole `config/` directory (which is code) on a volume.
- An earlier draft included a Caddy overlay for TLS; it was dropped once Coolify was confirmed as the host.

**Verification (2026-10-05, Docker 29.8.1 on Windows 11, local only)**

- Tested: image builds; clean first boot installs unattended in about 3.5 minutes; storefront returns 200 with title "Salisberg"; back office login page loads at `/admin-salisberg`; `/install` and `/admin` return 404; `img/logo.jpg` is served from the seeded volume; data paths are owned by `www-data`; recreating the `app` container boots in about 5 seconds without reinstalling and keeps all 300 tables; both containers report healthy.
- Not tested: a real deploy on Coolify, the `https://` install path (`PS_SSL_ENABLED` update) end to end, outgoing email, payments, and a volume that arrives root-owned from the host.

**Bug found during testing and fixed**

- The Debian `default-mysql-client` (MariaDB) refuses MySQL 8's self-signed certificate (`TLS/SSL error: self-signed certificate in certificate chain`). The first entrypoint used `mysqladmin ping` to wait for the database, so the wait never succeeded and every boot stalled for the full 120 seconds; the `mysql` call that enables SSL would have aborted an `https://` install.
- Fix: the entrypoint now talks to the database through PHP PDO (`db_query`), the same driver the app uses, and exits with the real error if the database is unreachable. A `[client] skip-ssl` config keeps the `mysql`/`mysqldump` CLI usable for manual work inside the container.

### 2026-10-05 — Upstream comparison (Qloapps/QloApps)

**Method:** shallow-fetched upstream `v1.7.0`, `v1.7.x` and `develop` into `refs/upstream/*` (no remote added) and compared trees with `git diff`.

**Findings**

- Upstream's latest stable release is **v1.7.0** (2025-07-04); the `v1.7.x` branch points at the same commit. There are no later stable fixes to pick up.
- This repo is byte-identical to v1.7.0 apart from `README.md`, `.gitignore`, the Docker files and this file, **and 152 files that are missing here**. Upstream's `.gitignore` ignores those paths and upstream force-added them, so they were dropped when the code was imported:
  - 138 module translation files under `modules/*/translations/` (bankwire, cheque, blocksocial, blockuserinfo, qlopaypalcommerce, qlohotelreview, dash*, stats*), including `en.php`
  - 11 images: room feature icons `img/rf/1-7.jpg`, `img/c/3-*_thumb.jpg`, `img/s/2.jpg`, `img/maintenance_banner.png` (used by the theme's `maintenance.tpl`)
  - `modules/qloautoupgrade/` (2 files) and `LICENSE.md` (the README links to it; OSL-3.0 expects it to ship)
- **Status: restored on 2026-10-05** on `salisberg-production` at the owner's request. The repo now differs from upstream v1.7.0 only by `README.md`, `.gitignore`, the Docker files and this file. Verified locally: amenity icons, maintenance banner and module translations are present in the image and served. Command used:
  `git diff --name-only --diff-filter=A HEAD refs/upstream/v1.7.0 | xargs -d '\n' git checkout refs/upstream/v1.7.0 --`.
- Upstream `develop` is about 15 months and roughly 4,000 changed files ahead of v1.7.0, restructured (`admin` → `admin-dev`, `install` → `install-dev`), still labelled 1.7.0.0, with no changelog entries. It is unreleased work in progress. **Decision: do not merge it.** Adopt the next tagged release when it ships; at that point `docker/entrypoint.sh` must be updated for the renamed `install`/`admin` directories.
- `composer.json` here still says `"php": ">=5.4 <8.0"`. That is stale upstream metadata; v1.7.0 supports PHP 8.1–8.4 (changelog #1464) and nothing in the image runs `composer install`.

### 2026-10-05 — Domain follows `PUBLIC_URL`; remaining gaps tested

**What**

- `docker/entrypoint.sh` now sets the shop domain (`qlo_shop_url`, `PS_SHOP_DOMAIN`, `PS_SHOP_DOMAIN_SSL`) and the HTTPS flags (`PS_SSL_ENABLED`, `PS_SSL_ENABLED_EVERYWHERE`) from `PUBLIC_URL` on every start. Controlled by `SYNC_DOMAIN` (default `1`), added to `docker-compose.yml` and `.env.example`.
- This replaces the install-time-only SSL update, and supersedes the earlier note in section 5 that `PUBLIC_URL` is only read at install.

**Why**

- The owner wants the app fully set up before DNS for salisberg.com is pointed at the server. The app can now be deployed and configured on a temporary Coolify URL, then moved by changing `PUBLIC_URL` (and the domain in Coolify) and redeploying. No database edits, no reinstall.

**How to move to the real domain**

1. Point DNS for `salisberg.com` and `www` at the VPS.
2. In Coolify, change the `app` service domain to `https://salisberg.com` and set `PUBLIC_URL=https://salisberg.com`.
3. Redeploy. The log line `Shop domain set to salisberg.com (ssl=1)` confirms it.

Set `SYNC_DOMAIN=0` only if the domain is to be managed by hand in the back office (for example with multiple shop URLs).

**Verification (local)**

- Root-owned, mode-700 `img`, `upload` and `/data` volumes: reproduced the 403 Forbidden from coollabsio/coolify#4058, then restarted; the entrypoint restored ownership and the image was served with 200.
- `PUBLIC_URL=https://staging.test` with `X-Forwarded-Proto: https` (simulating Coolify's proxy): storefront, images and back office login return 200 and every generated link uses `https://staging.test`. Plain HTTP is redirected (301) to HTTPS; a request for another host is redirected to the configured domain.
- Switching back to `http://localhost:8080` restored local access.
- Still not tested: a real Coolify deploy with a real certificate, outgoing email, payments.
