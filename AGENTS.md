# AGENTS.md — Salisberg

Guidance for any agent or developer working in this repository. Read it before changing anything, and update the change log at the bottom when you finish.

## 1. What this project is

- **Product name:** Salisberg. **Production URL:** https://salisberg.com
- **Base:** a fork of [QloApps](https://github.com/Qloapps/QloApps) (Webkul), an open-source hotel booking engine that is itself built on PrestaShop 1.6. License: OSL-3.0.
- **Direction (owner, 2026-10-07):** Salisberg stands on its own. It started from QloApps 1.7.0 but does not depend on QloApps or wait for its updates. The inherited code is ours to change. Rules 11, 18 and 28 are written for that.
- **Stack:** PHP 8.3 + Apache, MySQL 8.0, Smarty templates. No Composer dependencies at the root, no Node build step.
- **Hosting:** a VPS running [Coolify](https://coolify.io). Coolify builds `docker-compose.yml` from this repo and its own proxy terminates TLS for the domain.
- The repo was briefly named "StayFlow" (the GitHub repository is still `stayflow-hms`); the internal code, database prefix (`qlo_`) and many file names still say QloApps. That is expected.

License Migration — OSL 3.0 → MIT

This project is licensed under the MIT License by Salisberg.

All coding agents working on this repository must ensure the license migration is applied consistently across the entire repository.

When updating the license:

Replace all references to OSL, OSL-3.0, and Open Software License 3.0 with MIT, where those references describe this project's license.

Replace the existing OSL license text with the MIT License.

Update package.json and any other package/project metadata to use the SPDX identifier MIT.

Update all LICENSE, COPYING, README, documentation, source-code headers, package manifests, configuration files, and other files that explicitly reference the project's license.

Add or update SPDX headers to MIT where the project uses SPDX headers.

Search the entire repository, including hidden files and configuration directories, for stale OSL references.

Do not modify the licence files or notices of bundled third-party libraries (for example `tools/`, `js/jquery`, `js/tiny_mce`, Smarty, TCPDF, SimpleXLSX). The inherited QloApps/PrestaShop platform code is ours (section 1): its old file headers may be removed (owner, 2026-11-20; rule 14).

Do not modify historical changelogs or archived documents unless they incorrectly claim that the current version of this project is licensed under OSL.

Preserve existing copyright notices and attribution for those bundled libraries.

The copyright holder for this project is Salisberg.

After making the changes, perform a repository-wide search for OSL, OSL-3.0, and Open Software License and resolve every project-owned reference that should now say MIT.

Ensure there is only one authoritative license for the current project: MIT.

The final repository must consistently identify the project as MIT licensed by Salisberg.

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

11. **Fix things where they are defined.** The inherited platform code is ours (section 1, Direction), so a fault or an unwanted default in an inherited file is corrected in that file, not papered over from somewhere else. For styling this means changing the inherited stylesheet's rule instead of out-ranking it with `!important`. **`override/` is not used** (owner, 2026-11-19): a change to an inherited class or controller is made in that file, so the code says what it does where it lives. The existing overrides are being folded in (`CLEANUP-PLAN.md`, O1-O7). Features of our own that are not changes to inherited behaviour still go in our modules. Every change to an inherited file is verified in a running stack and recorded in section 6.
12. **Do not rename the `qlo_` table prefix or internal `QloApps`/`PrestaShop` identifiers** as part of branding work. Branding is done through shop name, theme, logos and translations.
13. **Match the surrounding code style** (PrestaShop 1.6 conventions: PSR-2-ish PHP, Smarty `.tpl`).
14. **Licence headers (owner, 2026-11-20).** Old QloApps/PrestaShop headers in inherited platform files may be removed; do not add them back. Bundled third-party libraries keep their licence files and notices.

### Process

15. **Verify before claiming.** Build the image and boot the stack locally (section 5) after any change to `Dockerfile`, `docker/` or compose files. Record what was and was not tested.
16. **Commit or push only when the owner asks.** Work on a branch, not directly on `main`.
17. **Record every meaningful change in section 6** with what, why and how.
18. **Upstream is a source of security fixes, not of releases.** Do not merge `Qloapps/QloApps` (neither `develop` nor a new tag) and never install the 1-Click Upgrade module. Do watch upstream's security advisories and fixes: a fix that applies to code we still carry is backported by hand and listed in `PATCHES.md` (rule 24). Bundled libraries (jQuery, Bootstrap scripts, TinyMCE, Smarty, TCPDF, SimpleXLSX) are kept current by us, from their own publishers.
19. **Files under upstream-ignored paths** (`img/**`, `modules/*/translations/*`) need `git add -f`. Check for silently dropped files after any import from upstream.
20. **Anything written at runtime outside `img/`, `upload/`, `download/` and `/data` is lost on deploy.** When adding or enabling a module that accepts uploads, add its folder to `PERSIST_DIRS` in `docker/entrypoint.sh`. (Background: change log, 2026-10-06.)
21. **Sanitise input and escape output.** Take request input through `Tools::getValue()`, cast numbers, use `pSQL()` or `(int)` for anything placed in SQL, and escape template output with `|escape:'html':'UTF-8'`.
22. **Deploy-time data steps must not assume production matches local.** Read the current state, act on it, and log what was actually changed.
24. **Security changes to vendor files go in `PATCHES.md`, in the same change,** with the upstream commit they mirror, so they can be dropped when upstream ships the fix. Audit reports and lists of unpatched issues stay in the git-ignored `/audit/` folder: this repository is public.

### Consistency and maintenance

25. **Extend what exists before adding something new.** Before creating a file, module, stylesheet, script or deploy step, look for the one that already does that job and add to it. A new one is justified only when nothing existing fits, and the change log must say why. The places that already exist:

    | Concern | Where it lives |
    |---|---|
    | Website look | `themes/hotel-reservation-theme/css/salisberg.css` |
    | Back office look, light and dark | `admin/themes/default/css/overrides.css` (colours only through its tokens) |
    | Small interface scripts (password eye, theme switch) | `modules/salisbergguide/views/js/`, loaded by `addInterfaceAssets()` |
    | Back office guides, Hotel Staff role | `modules/salisbergguide` |
    | Payment methods | `modules/salisbergpay` |
    | Platform settings changed without editing vendor files (for example the jQuery version) | `config/defines_custom.inc.php` |
    | Reports on screen and as PDF | `modules/salisbergreports` |
    | Checking a running stack after a change | `docker/smoke-test.sh` (pages load), `tests/e2e` (browser journeys), `tests/php` (money and safety rules); the last two local only |
    | Installing, enabling or disabling modules | `docker/setup-modules.php` |
    | One-off data or schema changes on deploy | versioned steps in `docker/entrypoint.sh` |
    | Web server headers and access rules | `docker/apache.conf` |
    | Request limits | `docker/ratelimit.php` |
    | Brand images | `docker/branding/make.php` |
    | Removing old data on purpose (by kind and age, and the daily automatic clean-up) | `modules/salisbergcleanup` |

26. **One way of doing each thing.** Follow the pattern already used in the file being edited: the same naming (`sb-`/`--sb-` prefixes, `SBPAY_` settings, `*_VERSION` step markers), the same structure, the same comment style. Do not introduce a second approach beside an existing one; if the existing approach is wrong, replace it everywhere in the same change.
27. **Leave no layered fixes.** When a rule or function is superseded, change or delete the original instead of adding an override after it. Sections titled "corrections" or "fix" appended to a file are not acceptable in a finished change.
28. **Remove dead code, with proof.** Code that nothing uses is deleted, not commented out or kept "just in case"; git keeps the history.
    - **Proof first:** search the whole tree for every reference (class name, file path, function name, selectors for CSS) and confirm none remain outside the file itself. Check `classes/ConfigurationTest.php` too: it lists files the installer expects to exist.
    - **Then verify:** rebuild, load the storefront and back office, and confirm no errors in the log.
    - **Our own code** (anything from rule 25's table): remove freely once proven unused.
    - **Inherited platform code:** the same proof is required, and it is harder to get, because the platform loads files by fixed path, by hook name and through the class index. Whole files or folders may go once provably unreferenced; a rule, function or block inside a file may go once nothing can reach it. Never move or rename a file (rule 29). Record each removal in the change log with the evidence.
    - **Not dead:** features that are switched off but may be switched on (reviews, testimonials, bank wire), and anything loaded by path, by a hook name or through the class index.
29. **Never move or rename a vendor file.** The back office, the installer and the autoloader locate files by path. (Background: change log, 2026-10-06, `admin/functions.php`.)

30. **Record every change in two more places besides section 6.** `CHANGELOG.md` gets a line for every change, in plain words, under the date and one of Added / Changed / Fixed / Security / Removed. If someone using the back office will notice the change, it also gets a line on the What's New page (`modules/salisbergguide/views/templates/admin/whats_new.tpl`), under the same date, placed for the role it concerns (everyone, manager, or developer team). Section 6 of this file remains the detailed record of how and why.

31. **Look for simplifications; propose them, then wait for approval.** The goal is a system a front-desk employee can use without training. Every agent follows `SIMPLIFY-REVIEW.md`:
    - check the screens you touch, plus one area from its rota each working session;
    - record each finding as a proposal there, with the problem, evidence, why it is needed, benefits, risks and how to undo it;
    - build only what the owner has marked Approved.

    Faults may still be fixed straight away. Code removal follows `CLEANUP-PLAN.md`.

### Guides

23. **Every feature must be documented in its guide, in the same change.** The back office guides live in `modules/salisbergguide/views/templates/admin/`. A feature is not finished until the guide is updated.
    - **Who uses it decides where it goes.** There are three roles: Hotel Staff (front desk), Hotel Manager (runs the hotel) and SuperAdmin (the developer team). Something front desk staff do goes in `staff_guide.tpl`. Something a manager or the developer team does goes in `admin_guide.tpl`; inside that file, anything only a SuperAdmin can do is wrapped in `{if $sb_is_admin}` with the standard "looked after by the developer team" note as the `{else}`, so a manager is never shown steps they cannot follow. A new page a manager needs is also added to `$managerAccess` in `salisbergguide.php`. A feature with both sides (for example a payment method: staff record payments, admins configure it) is covered in both, each from its own side.
    - **Never put admin-only instructions in the Staff Guide.** Staff must not be shown how to do things their role cannot do. If the feature adds a page staff need, also grant it to the Hotel Staff profile (`$staffAccess` in `salisbergguide.php`) and list it in the guide's menu table.
    - **Write what is on the screen.** Use the exact menu path, button and tab names as rendered, and check them against the running back office before writing. Add the section to the guide's table of contents.
    - **Changes and removals count too.** If a feature is changed, renamed, moved or removed, update or delete its guide text in the same change.
    - **Record it.** The change log entry for the feature must name the guide section that was added or updated.

## 3. How the Docker setup works

| File | Purpose |
|---|---|
| `Dockerfile` | `php:8.3-apache` (version set by the `PHP_VERSION` build argument) plus the extensions QloApps requires (gd, pdo_mysql, mysqli, soap, zip, intl, mbstring, opcache). Copies the repo into `/var/www/html` and keeps a pristine copy of `img`, `upload`, `download` in `/usr/src/salisberg-seed`. |
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

### 2026-10-05 — Salisberg Hotels branding; vendor branding removed from the surfaces people see

**What**

- **Name:** the shop is now "Salisberg Hotels" (`PS_SHOP_NAME`, `qlo_shop.name`, and the `SHOP_NAME` default for fresh installs).
- **Artwork:** `docker/branding/salisberg-master.png` is the owner-supplied master (wordmark lockup plus rounded-square mark). `docker/branding/make.php` crops and resizes it with GD into every asset:
  - storefront: `img/logo.jpg` (486x260), `img/favicon.ico` (16/32/48/64), `img/logo_stores.png|gif`
  - emails and PDF invoices: `img/logo_mail.jpg`, `img/logo_invoice.jpg` (white background so they stay legible in dark-mode mail clients)
  - back office: `img/qloapps@2x.png` and `img/qloapps-login*@2x.png` (login page), `img/prestashop-avatar.png` (default staff avatar), `admin/themes/default/img/qloapps-back-office-header.png` (top bar; dark ink recoloured white because the bar is dark)
- **Branding step in `docker/entrypoint.sh`:** when `BRANDING_VERSION` differs from `/data/.branding-version`, the files in `BRAND_FILES` are copied from the image into the `img` volume, the shop name and logo dimensions are set, the vendor's social links are cleared, and `PS_IMG_UPDATE_TIME` is refreshed so browsers refetch the logo and favicon.
- **Templates edited** (vendor name, links and promotions removed):
  - `themes/hotel-reservation-theme/header.tpl`: `generator` meta tag
  - `admin/themes/default/template/controllers/login/header.tpl`, `content.tpl`: page title suffix, logo alt text, copyright line, social links
  - `admin/themes/default/template/header.tpl`: "Explore QloApps Addons" button and update notice
  - `admin/themes/default/template/footer.tpl`: Webkul link, social links, vendor contact/forum/addons links
  - `admin/themes/default/template/controllers/dashboard/helpers/view/view.tpl`: Help Center panel, upgrade panel, recommended-addons banner
- **`Dockerfile`:** removes `docker/`, `docker-compose*.yml`, `.env.example`, `.gitattributes` and `.travis.yml` from the web root. They were publicly downloadable (no secrets, but not meant to be served).

**Why**

- The owner supplied the logo and asked that the product present as Salisberg Hotels with no QloApps branding.
- The `img` directory is a volume seeded once, so replacing files in the repo alone would never update an existing install; hence the versioned branding step. It is versioned rather than unconditional so that a logo uploaded later through the back office is not overwritten on every deploy.
- Admin theme templates cannot be replaced through `override/`, so these are direct edits (an accepted exception to rule 11). Expect conflicts here when adopting a new upstream release.

**Deliberately not changed**

- Internal identifiers and file names: the `qlo_` prefix, class names such as `AdminQloappsChannelManagerConnector`, image file names like `qloapps@2x.png` (rule 12). They appear only in URLs and page source.
- Licence headers and source copyright notices (rule 14, OSL-3.0).
- Deeper back office screens that still mention QloApps in their text: Modules and Services, the modules catalog/Addons pages, the 1-Click Upgrade module, and module descriptions. Roughly 100 template files; not touched yet.
- Demo data from the installer: the sample hotel "Hotel Prime" and the sample customer `pub@qloapps.com`. Replace or delete these in the back office.

**How to change the logo later**

1. Replace `docker/branding/salisberg-master.png` and adjust the `$LOGO`/`$ICON` crop boxes in `make.php` if the layout differs.
2. Run `make.php` (command in its header), bump `BRANDING_VERSION` in `docker/entrypoint.sh`, commit, deploy.

**Verification (local, against an existing install, which is the production situation)**

- Log showed `Branding v1 applied (Salisberg Hotels)`; a second restart did not re-apply it.
- Storefront: title, logo `alt` and `generator` read "Salisberg Hotels"; new logo and favicon served with a fresh cache-buster; zero occurrences of "qloapps" in the homepage HTML.
- Back office: login page and dashboard render without template errors after logging in; titles read "Salisberg Hotels"; the only remaining "qloapps" strings in the dashboard HTML are internal class and file names.
- `/docker/entrypoint.sh`, `/docker-compose.yml` and `/.env.example` now return 404.
- Not checked visually in a browser (no screenshot tooling in this session): logo size in the header and on the login page should be eyeballed after deploy. Emails and PDF invoices with the new logo were not generated.

### 2026-10-05 — Homepage redesign (visual layer)

**What**

- New stylesheet `themes/hotel-reservation-theme/css/salisberg.css`, linked last in `themes/hotel-reservation-theme/header.tpl` (after all theme and module CSS) together with two Google Fonts: Cormorant Garamond for headings and DM Sans for text.
- Design tokens at the top of the file (`--sb-green`, `--sb-gold`, `--sb-cream`, radius, shadow, font stacks) taken from the logo. Change the palette there.
- **Site-wide:** dark green contact strip, white header bar with the colour logo, green/gold buttons, dark green footer with gold headings.
- **Homepage only** (rules scoped with `#index`): gradient overlay on the hero photo, large serif hotel name with a letter-spaced eyebrow, search form as a floating white card with a gold button, serif section headings with a gold rule, rounded gallery tiles, amenities as cards on a cream band, room types as image-top cards with gold price and a solid button, testimonials as a centred serif quote on a cream band.
- Responsive rules for tablet and phone; transitions are disabled under `prefers-reduced-motion`.

**Why**

- The owner asked for a homepage of a modern standard. The stock layout had the logo floating as a white box on the hero photo, a blue accent unrelated to the brand, hairline-bordered tiles, and room descriptions overlaid on the photos.

**How / methods**

- CSS only, plus the one `<link>` block in `header.tpl`. No module templates, no vendor stylesheets and no JavaScript were edited, so the homepage modules (`wkroomsearchblock`, `wkabouthotelblock`, `wkhotelfeaturesblock`, `wkhotelroom`, `wktestimonialblock`) keep working and upstream updates to them still apply.
- Iterated against real renders: headless Edge driven over the DevTools protocol took full-page screenshots at 1440px and 390px after each change.
- To change the look: edit `salisberg.css` and bump the `?v=` number on its `<link>` in `header.tpl` so browsers fetch the new file.

**Verification (local)**

- Screenshots reviewed at 1440x900 and 390x844: homepage top to bottom, a room page and the sign-in page. No layout breakage found; inner pages pick up the new header, buttons and footer and are otherwise unchanged.
- Not tested: tablet widths in between, RTL languages, the phone search pop-up after tapping "Make Booking", Safari/Firefox, and the booking flow pages beyond the two above.
- Content is still the installer's demo data ("Hotel Prime", sample photos and prices in USD); the design is independent of it.

### 2026-10-05 — Inner pages restyled; demo identity replaced

**What**

- **Inner pages** (`salisberg.css`, new "Inner pages" section; stylesheet link bumped to `?v=2`): the stock blue and bright green are gone from search results, room pages, the cart pop-up, sign-in and contact. Covered: two-layer stock buttons flattened to brand green with gold hover, "Book Now"/"Select" buttons, tab and heading underlines, links, price slider, date-picker selection, info alerts, the search side panel, serif page and room headings, rounded panels and form fields with a gold focus ring.
- **Demo identity** (`docker/entrypoint.sh`, versioned by `CONTENT_VERSION`, marker `/data/.content-version`): on the next start, "The Hotel Prime"/"Hotel Prime" becomes "Salisberg Hotels" in the hero title, hotel name and descriptions, policies, the About Us page, the hotel category, the hotel address label and shop address line; `hotelprime@htl.com` becomes `booking@salisberg.com` for the shop email, the support email and the hotel's email; the homepage meta title becomes "Hotels & Hospitality" so the browser title is not the name twice.

**Why**

- The owner approved bringing inner pages in line with the homepage, supplied `booking@salisberg.com`, and asked for demo content to be switched to Salisberg from the code side to cut down manual editing in the back office.

**How / methods**

- Found what to restyle by running a script in the rendered pages that listed every element whose computed colour, background or border was still a stock blue or bright green, then overrode exactly those selectors.
- Found what to rename by dumping the database and grepping for the demo strings, then limiting the replacement to those tables and columns.
- Each SQL statement matches only values that still contain the demo text, so content already edited in the back office is not overwritten, and re-running is harmless.

**Deliberately left as demo data (needs the owner's real information)**

- The three sample guest reviews. They are invented people praising "Hotel Prime"; renaming them would publish fabricated reviews under the Salisberg name. Replace them with real ones or switch the block off (Modules → Testimonial block).
- Phone number `0987654321`, the street address (Monticello Dr, Montgomery, "Demo City", Alabama, United States), room types, photos, prices and the USD currency.
- The contact-form recipients still point at the administrator's login email, because `booking@salisberg.com` cannot receive mail until MX records and a mailbox exist for the domain.

**Verification (local)**

- Screenshots at 1440px of search results and a room page after the change: no stock blue or green left in view; layouts intact.
- After the content step: homepage hero and title read "Salisberg Hotels"; contact, room and About Us pages contain no "Hotel Prime" and no `htl.com`; the email shown is `booking@salisberg.com`. The log shows `Content v1 applied` once and not again on a plain restart.
- Not tested: checkout and payment pages, my-account pages, phone layouts of the inner pages, and whether mail sent from `booking@salisberg.com` is delivered (no SMTP or DNS mail records yet).

### 2026-10-05 — Full verification pass; health-check grace period raised

**What was run** (local, clean volumes, built from the working tree)

- `bash -n` on `docker/entrypoint.sh`, `docker compose config`, and a check that the entrypoint and Dockerfile are stored with LF endings.
- A from-scratch install: installer, domain sync, branding step and content step all ran in order and logged success.
- Ten storefront pages (home, search results, room, contact, sign-in, cart/checkout, properties, About Us, 404, password reset) and nine back office pages (login, dashboard, Orders, Room types, Customers, Modules, Themes, SEO & URLs, Employees) fetched and scanned for PHP errors, warnings, Smarty exceptions and SQL errors: none found.
- Assets served: stylesheet, logo, favicon, mail logo, amenity icon, back office logos. `/install`, `/admin`, `/docker/entrypoint.sh`, `/docker-compose.yml` and `/.env.example` return 404.
- Booking action in a real browser (headless Edge): clicking "Book Now" on a room page added the room to the cart, the confirmation pop-up opened, the cart count went from 0 to 1, and no JavaScript errors were raised.

**Change**

- `Dockerfile` health check `--start-period` raised from 300s to 900s. This run's first install took about 9 minutes on a busy machine (earlier runs took 3.5), and the container was reported "unhealthy" while the installer was still working. Only the very first start is affected; later starts take seconds.

**Not covered**

- Completing a booking through payment, sending email, and the Coolify deployment of these last changes.

### 2026-10-06 — Module pictures were lost on redeploy; now persisted and restored

**Problem**

- On the live site the interior gallery (12 pictures), amenities (4), footer payment icons (4) and guest photos (3) returned 404. The owner wants the sample pictures kept.
- Cause: a flaw in the Docker design from 2026-10-05. Four modules store uploaded pictures inside their own code folders, not under `img/`. Code folders are rebuilt from git on every deploy, so the pictures created by the installer disappeared on the first redeploy. Pictures uploaded to those sections through the back office would have been lost the same way.

**Fix** (`docker/entrypoint.sh`)

- `PERSIST_DIRS` lists the four folders. On every start each one is moved to `/data/persist/<same path>` in the `app_data` volume and replaced by a symlink, before the installer runs.
  - `modules/wkabouthotelblock/views/img/hotel_interior`
  - `modules/wkhotelfeaturesblock/views/img/hotels_features_img`
  - `modules/wkfooterpaymentblock/views/img/payment_img`
  - `modules/wktestimonialblock/views/img/hotels_testimonials_img`
- One-off repair, guarded by `/data/.sample-images-restored`: where one of those folders holds no pictures, they are rebuilt from the module's `dummy_img` folder, the same source the installer uses. It runs once, so pictures the owner later deletes on purpose stay deleted.

**Rule added (extends rule 1)**

- 20. **Anything written at runtime outside `img/`, `upload/`, `download/` and `/data` is lost on deploy.** When adding or enabling a module that accepts uploads, find where it writes and add that folder to `PERSIST_DIRS`. `docker diff <app container>` after using the feature shows what was written.

**How it was found**

- Fetched every image URL on the live homepage and checked the status codes, then ran `docker diff` on a freshly installed local container to list everything the installer writes outside the volumes. Also written there but safe to lose because they are regenerated or shipped in the repo: `config/xml` caches, module `config.xml` caches, language-pack copies under `mails/en` and `translations/en`.

**Verification (local)**

- Rebuilt on top of an install whose pictures had been wiped, the same state as production: the log showed `Restored sample pictures in …` for all four folders and every homepage image returned 200.
- Recreated the container again: pictures still served, and the repair did not run a second time.
- Full-page screenshot reviewed: gallery, amenities, room cards, guest photo and payment icons all display.
- Not yet verified on the live server; uploading a new picture through the back office and redeploying was not tested either.

### 2026-10-06 — Currency, security hardening, payments, navigation, forms, guides and staff role

All of the following are driven from code and applied on deploy. Nothing here needs manual steps on the server except where "Owner must" says so.

#### Currency: Ghana cedi

- **What:** `docker/entrypoint.sh` step guarded by `CURRENCY_VERSION` / `/data/.currency-version`. The single currency row created by the installer (US dollar) is relabelled to Ghana Cedi, `GHS`, numeric code 936, sign `GH₵`.
- **Why relabel instead of adding a currency:** carts, the default-currency setting and payment-module permissions all point at that row, so nothing else has to change and no exchange rate is involved. Sample prices keep their numbers and are simply shown in cedis.
- **Guard:** the statement does nothing once any order exists, because it would relabel money already charged.
- `db_query` now connects with `charset=utf8mb4` (needed for `₵`) and prints the first column of a SELECT, so steps can report what they did.

#### Security review and hardening

- **Method:** probed the live site (response headers, cookie flags, about 45 sensitive paths, a reflected-input probe, a quote in a numeric parameter) and scanned the code with grep heuristics for request values placed into SQL without a cast or `pSQL()`, and for raw superglobals in the hotel modules and controllers.
- **Findings, no change needed:** session cookies are `Secure; HttpOnly; SameSite=Lax`; `config/`, `classes/`, `override/`, `mails/`, `.env*` and `.git` are not served; debug mode is off and no SQL or PHP error text leaked; the scans found no request value concatenated into SQL (the hits were admin redirects) and no raw superglobals in the hotel modules. The core relies on `Tools::getValue()`, `(int)` casts, `pSQL()` and Smarty `escape`.
- **Findings, fixed** (`docker/apache.conf`, `Dockerfile`, `docker/entrypoint.sh`):
  - Added `X-Content-Type-Options`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`, and `Strict-Transport-Security` when the proxy reports HTTPS. Removed the `Powered-By: QloApps` header.
  - Script execution is denied in every folder that receives uploads: `img/`, `upload/`, `download/`, any `modules/*/views/img/`, and `/data`. Before this, `upload/` and the module picture folders would run a `.php` file if one were ever uploaded.
  - `CHANGELOG.txt` and `composer.json` are removed from the web root (they disclose the exact platform version).
  - The entrypoint refuses install variables containing a quote, backslash, backtick or dollar sign, since they are placed inside a quoted shell command.
- **Limits:** this was a targeted review, not a full audit or penetration test. Not covered: every admin controller, file-upload validation inside each module, rate limiting on the login and contact forms, and a `Content-Security-Policy` (the theme uses inline scripts, so a strict one would break it).
- **Rule 21:** new code must take request input through `Tools::getValue()`, cast numbers, use `pSQL()` (or `(int)`) for anything placed in SQL, and escape output in templates with `|escape:'html':'UTF-8'`.

#### Payments: cash and Mobile Money

- **What:** new module `modules/salisbergpay` ("Salisberg Pay"), modelled on the bundled `bankwire` module. One module, two guest options: "Pay cash at the hotel" and "Pay by Mobile Money (MoMo)". Both create the booking as **Awaiting payment**; staff record the money in the back office. Nothing is charged automatically and no gateway is involved.
- Settings page (Modules and Services → Manage Modules → Salisberg Pay → Configure): switch each method on or off; MoMo network, registered name, number, optional note. Input is validated (number: digits, spaces, leading `+`; names: no `< > = { }`; note: tags stripped, 500 characters).
- Mobile Money stays hidden from guests until a number is saved, so nobody is told to send money to a blank number.
- `docker/setup-modules.php` (copied outside the web root by the Dockerfile, run by the entrypoint as `www-data`, guarded by `MODULES_VERSION`): activates Ghana as a country, installs the module, grants it to Ghana, and **disables bank wire and cheque**, which the hotel does not use.
- **Owner must:** enter the real Mobile Money number and account name in the settings page. Until then only cash is offered.
- **Why not a real MoMo gateway:** that needs a merchant account and API credentials (MTN MoMo API, Paystack, Hubtel or similar). The module is the "for now" version the owner asked for; a gateway can replace the manual MoMo option later.

#### Navigation bar and forms (`salisberg.css`, link bumped to `?v=6`)

- **Desktop navigation:** from 1200px wide the menu links are shown inline in the header with a gold underline on hover, and the hamburger is hidden. Below 1200px the slide-in panel is kept, restyled in brand colours. CSS only; the `blocknavigationmenu` module and its links (editable in the back office) are untouched.
- **Bug fixed:** after the header became white, the signed-in account button (the guest's name, with Accounts / Bookings / Logout) was white on white and could not be seen. It is now a visible button with a styled dropdown.
- **Forms:** consistent fields site-wide (46px height, rounded, gold focus ring), styled select/checkbox/radio wrappers, card-style panels for sign-in, password, contact and account forms, brand-coloured alerts.
- **Checkout:** the stock blue and green are gone from the cart, guest and payment steps; payment options are cards.
- **Logo:** reduced in the header (72px to 54px desktop, 48px to 42px phone) at the owner's request. The back office top-bar logo was regenerated with more padding so it is no longer cropped.

#### Back office guides and the Hotel Staff role

- **What:** new module `modules/salisbergguide`. Adds a **Guides** menu with:
  - **Staff Guide** (11 sections): signing in, finding pages, making a booking, finding bookings, recording cash and Mobile Money payments, check-in and check-out, changing and cancelling, guest records, messages and refund requests, what each status means, what to do when something goes wrong.
  - **Admin Guide** (14 sections): staff accounts and permissions, hotel details, room types, prices and discounts, extra services, payment settings, currency and taxes, refunds, website content, email, maintenance mode, backups, what must be left to the developer, reports.
- **New profile "Hotel Staff":** can view the dashboard, room types, invoices and booking carts; view/add/edit bookings, Book Now, customers, addresses and customer service; view/edit refund requests; read the Staff Guide. No delete rights anywhere, and no access to settings, prices, modules, employees or the Admin Guide.
- **The Admin Guide is closed twice:** the profile has no permission for its menu entry, and the controller itself only renders for a SuperAdmin.
- Menu names, button labels and tab names in the guides were checked against the rendered back office pages before writing. Guide text lives in `modules/salisbergguide/views/templates/admin/*.tpl`.
- An existing "Hotel Staff" profile is never reset, so permissions adjusted by hand in Administration → Permissions survive deploys.
- **Owner must:** create an account for each employee in Administration → Employees with the Hotel Staff profile.
- **Why a menu entry had to be handled specially:** `Tab::add()` returns false from the command line because recording permissions needs an employee in context. `setup-modules.php` therefore acts as the first administrator, and the module judges success by whether the menu entry exists. `ensureSetup()` re-creates anything missing on every run.

#### Verification (local)

- Fresh rebuild; storefront pages (home, search, room, contact, sign-in, checkout, properties, About Us, payment summary) and back office pages (dashboard, orders, the guide pages, payment settings, employees, permissions, module configuration) return 200 with no PHP, Smarty or SQL errors.
- **Payments, in a real browser as a signed-in guest:** room added to cart, checkout steps completed, both options shown; a cash booking and a Mobile Money booking were each created as "Awaiting payment" with the correct method name and GH₵ totals, and the confirmation page showed the right instructions.
- **Roles:** signed in as a test Hotel Staff employee: the menu shows only the permitted pages; Orders, Book Now and Customers open; Employees, Modules, Preferences, hotel settings and the Admin Guide are refused. As SuperAdmin the guide pages open.
- **Security:** headers present (HSTS only with HTTPS); `index.php` inside `img/`, `upload/`, `download/` and a module picture folder returns 403 while pictures in the same folders return 200; `CHANGELOG.txt` and `composer.json` return 404.
- **Navigation:** screenshots at 1440, 1280 and 390 wide, signed out and signed in.
- **Not tested:** a deploy of all this to the live server; email sending; widths between 992 and 1199; Safari and Firefox; the staff role against every action it can reach (for example adding a payment and changing a status as staff); a refund through to completion.
- Local test data created during this work (two bookings, a test guest, a test employee, a test MoMo number) exists only in the local database.

### 2026-10-06 — Correction: currency step did nothing on the live site

- **What happened:** after the deploy the live site still showed prices as `1 750,00 €`. The live installer had created a **euro** default currency; the local one had created US dollars (the installer picks whichever localisation pack it finds, and falls back to a generic one for Ghana). Currency step v1 only matched `iso_code='USD'`, so on production it changed nothing while logging "done".
- **Fix (`CURRENCY_VERSION=2`):** the step no longer assumes which currency exists. If there are no orders, it takes an existing GHS row, or otherwise the current default currency whatever it is, sets it to Ghana Cedi (`GHS`, `GH₵`, format `GH₵1,750.00`, rate 1), makes it the default, deactivates any other currency, grants it to the payment modules, and moves open carts to it. If orders exist it logs "skipped" and leaves everything alone.
- **Lesson, now rule 22:** a deploy-time data step must not assume the production database matches the local one. Read the current state and act on it, and log what was actually changed rather than "done".
- **Verification:** run against a scratch copy of the currency tables set to euro: with no orders the result was a single active GHS currency as default; with one order present the step skipped and left the euro in place. Not yet verified on the live site.
- **Also confirmed live after the previous deploy:** stylesheet `?v=6`, all 24 homepage pictures load (the gallery, amenities and payment icons are back), the four security headers are present, `CHANGELOG.txt` and `composer.json` return 404, script execution is blocked in `img/` and `upload/`, and the Salisberg Pay and Salisberg Guide modules are being served.
- A scratch database named `sbtest` was left in the **local** MySQL container by this test. It is not part of the app; `docker compose down -v` removes it along with the rest of the local data.

### 2026-10-06 — Security fixes from the audit, backups, and a restore test

An audit of the codebase was carried out and the owner approved fixing its findings from the most to the least severe. The audit report and the fix plan are kept out of this public repository (`/audit/` is git-ignored and excluded from the Docker image).

#### Upstream security fixes backported

- **What:** five commits from upstream `develop` applied to the 1.7.0 tree, plus one fix of our own. All are listed in `PATCHES.md` with the upstream commit each mirrors.
  - Passwords move from MD5 to bcrypt. Existing accounts keep working and are upgraded at their next successful login.
  - SQL injection in back office address and customer-message handling.
  - Booking-document upload validation, and escaping of list filter values.
  - Path traversal in the email template preview.
  - Cross-site scripting in hotel feature management.
- **Why:** these are published CVEs affecting QloApps 1.7.0. Upstream has fixed them on its development branch but has not released a version containing them.
- **How:** `git fetch <upstream> <commit>`, `git diff <commit>^1 <commit>`, `git apply`. All applied without conflicts.
- **Schema step** (`SCHEMA_VERSION`, marker `/data/.schema-version`) in `docker/entrypoint.sh`: widens `passwd` to 60 characters in `qlo_customer`, `qlo_employee` and `qlo_referrer` before Apache starts, and sets the back office session lifetime to 12 hours if it is still at the stock 480.
- **Rule 24:** any change to a vendor file for security reasons must be added to `PATCHES.md` in the same change.

#### Hotel reviews: disabled and fixed

- **What:** `docker/setup-modules.php` now disables `qlohotelreview` on every deploy (`MODULES_VERSION=3`). The upload code was also fixed: only the signed-in customer who made the order can post a review, and uploads must be real images, are re-encoded and are saved as `<n>.jpg`.
- **Why:** CVE-2025-67325 (rated 9.8). The endpoint accepted uploads from anyone, protected only by a token that is printed on public pages, and kept the file extension sent by the browser. The owner chose to switch reviews off until they are needed.
- The review image folder was added to `PERSIST_DIRS`, so review pictures will survive deploys once the feature is used.
- **To enable reviews later:** remove `qlohotelreview` from the disable list in `docker/setup-modules.php`, bump `MODULES_VERSION`, and update the Admin Guide (rule 23).

#### Payment confirmation token

- `salisbergpay`: the cash / Mobile Money confirmation form now carries the customer token, and the validation controller only accepts a POST with a valid token. A request without it is sent back to the checkout and creates no booking.

#### Backups

- **What:** a `backup` service in `docker-compose.yml`, running `docker/backup.sh` from the app image. On first start, and then once a day at `BACKUP_HOUR_UTC` (default 02:00), it writes to the `db_backups` volume:
  - `salisberg-db-<timestamp>.sql.gz.enc`: a full database dump;
  - `salisberg-data-<timestamp>.tar.gz.enc`: `/data`, which holds the settings file with the encryption keys and the module pictures.
- Files are encrypted with AES-256 using `BACKUP_PASSPHRASE`. Files older than `BACKUP_KEEP_DAYS` (default 14) are removed. A dump smaller than 20 KB is treated as a failure.
- **Owner must:** set `BACKUP_PASSPHRASE` in Coolify to a long random value and keep a copy of it off the server. Without the passphrase the backups cannot be read. If it is left unset, backups are written unencrypted and a warning is logged every run.
- **Not covered yet:** copying backups off the server (needs a storage provider chosen by the owner), and the `app_img` and `app_upload` volumes (room and hotel photographs). A backup kept only on the same server does not survive losing that server.

**Restore procedure** (run inside the `backup` container; replace the file name):

```bash
export MYSQL_PWD="$DB_PASSWORD"
f=/backups/salisberg-db-YYYYMMDD-HHMMSS.sql.gz.enc
# check the file first
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass env:BACKUP_PASSPHRASE -in "$f" | gzip -t && echo OK
# restore into the live database (this replaces its contents: stop the app service first)
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass env:BACKUP_PASSPHRASE -in "$f" | gunzip | mysql -h"$DB_HOST" -u"$DB_USER" "$DB_NAME"
```

To restore `/data`, decrypt the matching `salisberg-data-…` file the same way and extract it with `tar -xzf - -C /` in a container that has the `app_data` volume mounted read-write.

**Rotating the database passwords.** `MYSQL_PASSWORD` and `MYSQL_ROOT_PASSWORD` are only read by MySQL when its data volume is first created. Changing them in Coolify alone locks the app out. Do both in one sitting:

1. In the `db` container: `mysql -uroot -p` (current root password), then
   `ALTER USER 'salisberg'@'%' IDENTIFIED BY '<new app password>'; ALTER USER 'root'@'%' IDENTIFIED BY '<new root password>'; ALTER USER 'root'@'localhost' IDENTIFIED BY '<new root password>'; FLUSH PRIVILEGES;`
2. In the `app` container, edit `/data/settings.inc.php` and set `_DB_PASSWD_` to the new app password.
3. In Coolify, set `MYSQL_PASSWORD` and `MYSQL_ROOT_PASSWORD` to the same new values, then redeploy.
4. Check the site loads and the `backup` log shows a successful dump.

#### Other

- `Permissions-Policy` header added (camera, microphone, geolocation, payment and USB denied).
- Admin Guide updated (rule 23): section 1 notes the 12-hour session, section 9 notes that Hotel Reviews is switched off, section 12 describes the automatic backups.

#### Verification (local)

- **Passwords:** before the change all four accounts held 32-character MD5 hashes. After deploying the patch: a wrong password was refused for both an employee and a customer; the administrator, a Hotel Staff employee and a customer each signed in with their existing password; their stored hashes became 60-character bcrypt; their sessions kept working; and a second sign-in against the upgraded hash succeeded. An account that did not sign in stayed on its old hash, as designed.
- **Reviews:** an anonymous upload request carrying a valid module token returned 404 with the module disabled, and nothing was written to the review folder.
- **Payment token:** POST without a token, POST with a wrong token, and a GET were each redirected to the checkout and created no order. A full browser booking through checkout with cash succeeded and the form carried the token.
- **Backports:** fourteen back office pages in the patched areas and eight storefront pages returned 200 with no PHP, template or SQL errors. A script payload sent as an order-list filter was not reflected unescaped. A path-traversal request for the settings file through the email preview returned no credentials.
- **Backups:** the service wrote an encrypted dump on first start. Restored into a scratch database: 300 of 300 tables, matching row counts on eight tables including orders, customers and configuration, and an identical table checksum. A wrong passphrase was rejected.
- **Fresh install** with the patched installer: see the status line below.
- **Not tested:** any of this on the live server; password reset by email; the review upload fix with the module enabled (it is disabled); restoring the `/data` archive; the three back office pages touched by the SQL-injection patch beyond loading them.

### 2026-10-06 — Minimal desktop navigation

- **What:** on screens 1200px and wider the header now shows four links (Amenities, Rooms, About Us, Contact Us), the cart and an outlined Sign in button. Home, Our Properties, Interior, Testimonials and Legal Notice are hidden on desktop only. Links are uppercase, lighter and more widely spaced. Stylesheet link bumped to `?v=7`.
- **Why:** the owner asked for a more minimal desktop bar and said some items did not need to be there. The logo already links home; there is a single property; Legal Notice is in the footer; Interior and Testimonials are sections a visitor scrolls past.
- **How:** CSS attribute selectors on each link's address in `salisberg.css` ("Desktop navigation: minimal"). The links themselves are untouched in the back office, so phones and tablets still get the full menu. To show an item on desktop again, remove its selector from that list.
- **Caveat:** the selectors match link addresses. If friendly URLs are switched on, or a link is renamed in the back office, re-check which items show.
- **Verification:** screenshots at 1440, 1280 and 1024 wide. Not checked signed-in at the new style, nor in Safari or Firefox.
- No guide change needed: this is visual only and adds no feature for staff or administrators.

### 2026-10-06 — Remaining back office XSS fixes; file manager SVG; token CVE assessed

**What**

- Backported two more upstream commits (both listed in `PATCHES.md`):
  - `1d06fd3` (pull request #1884), CVE-2026-103587: the back office Book Now search now validates its date parameters.
  - `7ed467d` (pull request #1899), CVE-2026-103588, -103589, -103590: escaping in the "Transplant a module" form, the room type editor (room number, floor, status, comment) and the length-of-stay fields.
- `admin/filemanager/config/config.php`: `svg` removed from the allowed upload types (CVE-2026-25558: script embedded in an SVG runs when someone opens the file).
- **CVE-2025-10759 assessed, no code change.** The published report is about the customer logout link: its token sits in the URL and can be reused, so someone who obtains it can log that customer out. It does not give access to an account. The `Referrer-Policy` header already set stops the token leaking to other sites, and the token changes whenever the customer's password hash changes. Accepted as low risk.

**How**

- `1d06fd3` applied cleanly with `git apply`. `7ed467d` applied except for one file, `admin/themes/default/template/controllers/products/configuration.tpl`, whose surrounding lines differ from upstream's development branch; its five one-line changes were made by hand to match the upstream diff exactly.

**Verification**

- Docker was not running on the development machine for this session, so **none of this was run in the application**. What was checked instead:
  - PHP syntax (`php -l`, PHP 8.3) on all 33 PHP files changed since the deployed branch: no errors.
  - Smarty syntax: the nine changed or new templates were compiled standalone with the bundled Smarty 4 and unknown plugins stubbed: all compiled. The same check was confirmed to fail on a deliberately broken template.
  - `bash -n` on both shell scripts; both stored with LF endings.
- **Still to do before relying on it:** load the Book Now page, the room type editor (Rooms and Length of Stay tabs), Modules → Positions → Transplant a module, and the file manager in a running stack, and confirm they behave as before.

**Deployment note**

- At the time of writing, the security work sits on the local branch `develop`, pushed to `origin/production`. The live site is built from `salisberg-production`, which does not contain it. Nothing from the security fixes is live until `develop` is pushed to `salisberg-production`.
- `/audit/` was also added to `.git/info/exclude` on the development machine, so the private audit files are ignored on every branch, including older ones whose `.gitignore` predates the rule.

**Follow-up, same day: run in the application.** Docker was started and the changes above were exercised in a local stack before deployment:

- Back office Book Now: loads, lists rooms for valid dates, and a script payload in `date_to` and `id_room_type` is not reflected.
- Room type editor: the page and its Rooms, Length of Stay and Information tabs load without errors.
- Modules → Positions and the "Transplant a module" form: load; a script payload in `exceptions[…]` is not reflected. A request sending `exceptions` as plain text instead of a list returns a 500; that is existing upstream behaviour (the same code is in stock 1.7.0), reachable only by a signed-in administrator, and shows no error detail.
- File manager dialog loads; `svg` is no longer in its allowed list.
- Dashboard, Orders, Customers, Modules, and the storefront home, room and checkout pages: 200, no PHP, template or SQL errors.

**Deployed:** `develop` was pushed to `salisberg-production` on 2026-10-06 at the owner's instruction, in two pushes: first the work tested the previous day, then these changes once tested.

**Commit messages carry no AI attribution**, at the owner's instruction.

### 2026-10-06 — Rate limiting on sign-in and forms; Content-Security-Policy

#### Request limiter

- **What:** `docker/ratelimit.php`, loaded before every web request through `auto_prepend_file` in `docker/php.ini` (copied to `/usr/local/share/salisberg/` by the Dockerfile, outside the web root). It counts POSTs per visitor address and refuses further ones once a limit is reached:

  | Form | Limit per address |
  |---|---|
  | Back office sign-in | 10 in 10 minutes |
  | Back office "forgot password" | 5 an hour |
  | Guest sign-in | 10 in 10 minutes |
  | New guest account | 10 an hour |
  | Password reset request | 5 an hour |
  | Contact form | 6 an hour |
  | Newsletter sign-up | 10 an hour |

- **Why:** the audit found no limit on password guessing against guest accounts, and none on the forms that send email. The stock back office has its own attempt setting; the storefront has nothing.
- **How it behaves:** a blocked visitor gets a short "Too many attempts. Please wait N minutes" page with status 429 and a `Retry-After` header. The back office sign-in form only displays messages that arrive as a normal JSON reply, so for that form the same message is returned with status 200 in the shape the form expects. Every block is written to the PHP error log as `[salisberg-ratelimit] blocked <rule> from <address>`.
- **Design choices:**
  - No application file is modified; the limiter can be removed by deleting one line from `php.ini`.
  - Counters are small files under the system temp folder, cleared on redeploy. No database, no extra service.
  - If the limiter cannot create or open its file, the request is allowed. It must never be the reason the site is down.
  - The visitor address is `REMOTE_ADDR`, which Apache's `mod_remoteip` has already set from the proxy's `X-Forwarded-For`.
  - Only the listed forms are counted. Browsing, searching, adding to the cart and checking out are not limited.
- **Limits live in** the `$rules` array at the top of `docker/ratelimit.php`.
- **Known trade-off:** everyone at the hotel shares one internet address, so the back office limit is shared between staff. Ten wrong passwords in ten minutes locks all of them out of signing in for up to ten minutes. People already signed in are not affected.

#### Content-Security-Policy

- **What** (`docker/apache.conf`): `object-src 'none'; base-uri 'self'; frame-ancestors 'self'`, plus `upgrade-insecure-requests` when the request arrived over HTTPS. Any SVG served from an upload folder is sent with a sandboxing policy and as a download, so script inside it cannot run.
- **Why only these rules:** they block plugins, `<base>` hijacking and framing by other sites, and nothing in the platform trips them. A `script-src` rule would break the theme and the back office, which rely on inline scripts; it needs a report-only trial with somewhere to collect the reports first.
- `upgrade-insecure-requests` is tied to HTTPS so local development over plain HTTP keeps working.

#### Guides (rule 23)

- Staff Guide, section 11 "When something goes wrong": new first entry explaining the "Too many attempts" message.
- Admin Guide, section 1 "Staff accounts and what they can see": new "Sign-in and form limits" subsection with the limits table and the shared-connection note.

#### Verification (local, running stack)

- `apachectl -t` reports "Syntax OK"; the container starts and reports healthy; `php -i` shows the prepend file is active; no PHP fatal errors in the log.
- Guest sign-in with wrong passwords from one address: attempts 1–10 answered normally, 11 and 12 returned 429 with `Retry-After`. A different address at the same moment was not affected, and the blocked address could still browse pages.
- Back office sign-in through `ajax-tab.php` (the address the page really posts to): attempt 11 returned the "Too many attempts" message. In a real browser the sign-in page showed that message in its normal error box.
- Contact form blocked on the 7th post in an hour; password reset on the 6th. Twenty cart requests in a row were all served.
- A correct administrator sign-in from an address that had not been blocked worked.
- Content-Security-Policy present once per response, with `upgrade-insecure-requests` only when HTTPS was signalled. A browser run over seven pages (home, room, search, checkout, contact, sign-in, back office sign-in) recorded no policy violations and no JavaScript errors.
- Dashboard, Orders, Room types, Modules and both guide pages load without errors; the new guide text is displayed.
- **Not tested:** behaviour behind Coolify's real proxy (the visitor address there comes from the proxy's header; if every visitor appeared as one address, the limits would be shared by everyone); newsletter and account-creation limits; the SVG sandbox header with a real SVG file.
- **After deploying, check on the live site** that two different networks (for example Wi-Fi and mobile data) are limited separately. If they are not, remove the `auto_prepend_file` line and redeploy.

### 2026-10-06 — Sample homepage reviews hidden; owner decisions on open items

#### Homepage "What our guests say" hidden

- **What:** `docker/setup-modules.php` (`MODULES_VERSION=4`) disables the `wktestimonialblock` module and deactivates the menu link that scrolls to it. It does this **once**, recorded by `/data/.testimonials-hidden`.
- **Why:** the block showed three invented reviews praising "Hotel Prime". The owner chose to hide the section until real reviews exist.
- **Why once, unlike bank wire, cheque and hotel reviews:** the owner will switch this one back on from the back office when real reviews are ready, and a later deploy must not undo that.
- **Guide (rule 23):** Admin Guide, section 9 "Website content", new subsection 'Homepage guest reviews ("What our guests say")' explaining how to enable the block and replace the samples.
- **Verification (local):** the log showed the block disabled; the homepage returned 200 with no errors, no testimonial section, no testimonial menu link and no remaining "Hotel Prime" text, while the gallery, amenities and rooms sections were still present. A second start did not repeat the step. The Admin Guide page rendered all 14 sections with the new text.
- **Caught before commit:** an unescaped apostrophe in the new guide text was a Smarty syntax error that would have broken the Admin Guide page. The standalone template compile check found it. Avoid apostrophes inside `{l s='…'}` strings, or escape them, and run the template check on every guide edit.

#### Owner decisions (2026-10-06)

| Item | Decision |
|---|---|
| Administrator password | Owner changes it in the back office |
| Mobile Money number | Owner enters it in Salisberg Pay settings |
| Backup passphrase | Generated into the owner's private `.env.coolify`; owner adds it in Coolify |
| Off-server backup copies | Decide later |
| Coolify "Build Variable" boxes | Owner unticks them |
| Database passwords | Leave for now; revisit once a staging copy exists |
| Staging application | Later, before opening to guests |
| Hotel phone and address | Owner enters them in Manage Hotel |
| Room types, photos, prices | Owner enters them in Manage Room Types |
| Email | Zoho Mail for `@salisberg.com`; DNS records and SMTP settings still to be set up |
| Repository visibility | Stays public |

### 2026-10-06 — Back office restyled

- **What:** `admin/themes/default/css/overrides.css`, the file upstream provides (empty) for back office customisation and loads last on every admin page including sign-in. It now carries the Salisberg look:
  - deep green top bar and side menu with a gold marker on the active item; branded submenu and footer;
  - DM Sans in place of Open Sans and the condensed capitals; slightly larger text; calmer panel headings and table headers;
  - rounded panels, buttons, fields, dropdowns and modals; gold focus ring on fields; green primary buttons; green/gold links, tabs, pagination, badges and Yes/No switches;
  - the logo in the top bar sized so it is no longer cropped, and the version number beside it hidden;
  - an icon for the Guides menu entry;
  - the vendor's "Recommendations" toolbar button and module-promotion panel hidden on every page;
  - the sign-in page in the same palette.
- **Why:** the owner asked for the admin panel's look to be refined; it was still the stock grey-and-blue PrestaShop 1.6 theme.
- **How:** CSS only, in the one file meant for it. No vendor stylesheet, template or script was edited, and no element was moved, so the screens still match the guides. Checked by screenshot before and after on the sign-in page, dashboard, Orders list, Customers list, the room type form and the employee form.
- **Limits:** this changes appearance, not behaviour. Pages still reload on every action and forms are as dense as before. Not checked: every one of the 79 menu pages, the "Top" menu orientation, right-to-left languages, and screens narrower than a laptop.
- **Guides:** no change needed; nothing a user does has moved or been renamed.
- **Considered and not done:** replacing the back office with a Filament (Laravel) admin. Filament cannot be embedded in this application; it would be a second application writing to the same database, bypassing the booking, pricing and availability rules that live in the PHP classes, or re-implementing them. That is a rewrite of the back office, not a restyle. See the audit's recommendation (modernize incrementally).

### 2026-10-06 — Show/hide button on password fields

- **What:** every password field, on the website and in the back office, now has an eye icon at its right edge. Clicking it shows what was typed; clicking again hides it.
- **Where:** `modules/salisbergguide/views/js/password-toggle.js` and `views/css/password-toggle.css`, added to pages by the Salisberg Guide module through three hooks registered in `ensureSetup()`: `header` (website), `actionAdminControllerSetMedia` (back office) and `actionAdminLoginControllerSetMedia` (back office sign-in). `MODULES_VERSION=5` so the hooks are registered on the next deploy.
- **Why this way:** one script for all password fields instead of editing each form template. The button is positioned over the field, not wrapped around it, so no existing form layout changes. No vendor file is touched.
- **Behaviour:**
  - Fields that appear later (checkout sign-in, "Change password…" in the back office) get the button too.
  - A field is switched back to hidden when its form is submitted, so a password is never sent or left on screen as plain text by accident.
  - The button is a real `<button>` with a label that changes between "Show password" and "Hide password", reachable by keyboard.
- **Guide (rule 23):** Staff Guide, section 1 "Signing in and your account": new step describing the eye icon.
- **Verification (local, real browser):** on the website sign-in page (desktop and phone width), the back office sign-in page and the new-employee form, the button sits inside the field; a click changes the field from hidden to visible and back; submitting the form resets it to hidden; no JavaScript errors. Screenshots reviewed for the website and back office sign-in pages.
- **Not tested:** the account-creation, "my details" and checkout-registration forms individually (they use the same script), Safari and Firefox, and right-to-left languages.

### 2026-10-06 — Back office: light layout modelled on the owner's reference

- **What:** `admin/themes/default/css/overrides.css` rewritten. The owner showed the admin panel of another product they use (Krayin CRM) as the look to aim for; the earlier dark-green restyle from the same day is replaced by:
  - a white top bar, 60px tall, with the colour logo, notification icons, Quick Access, "My site" and the employee name as a pill;
  - a white side menu, 240px wide, with larger rows, outlined grey icons and a filled green pill on the active item; submenu items indented on a thin rule, the current one on a pale green chip; the search box as a rounded field at the top;
  - a soft grey page background with white, rounded, bordered cards and no shadows; page title on the background without a white strip; action buttons outlined in green;
  - Inter as the typeface; table headers small, grey and uppercase; rounded fields with a green focus ring; pill-shaped Yes/No switches;
  - the stock bright blue replaced wherever a scan of rendered pages found it (dashboard figures, date switcher, availability buttons, info notes, hint labels);
  - the vendor's "Recommendations" button still hidden; Guides keeps its icon.
- **Sizes changed, deliberately:** the stock frame is a 36px bar and a 210px menu, fixed in several places. Top bar, menu width, page-head offset and content margin are all set from two variables at the top of the file (`--sb-top`, `--sb-side`) so they stay consistent.
- **Collapse-to-icons removed:** the stock "collapse menu" mode has many size rules of its own that broke the new layout, so the control is hidden and the menu always shows in full from 768px up. A user who had collapsed it before sees the full menu.
- **Below 768px** the stock compact layout (icon rail, 36px bar) is kept, with the desktop shapes undone so nothing overlaps.
- **How:** CSS only, in the file upstream provides for this. No template, script or vendor stylesheet was edited. The logo in the top bar is the existing `img/qloapps@2x.png` colour lockup, referenced as `/img/…`, which assumes the site is served from the domain root.
- **Verification (local, real browser screenshots):** sign-in page, dashboard, Orders, Customers, the room type form, the employee form, Book Now and the Staff Guide at 1440 wide; Orders at 1024 and 700 wide; and the previously-collapsed state forced on. No JavaScript errors. Menu names, buttons and tabs are where they were, so the guides are unchanged.
- **Not checked:** the other back office pages one by one (79 menu entries), the "Top" menu orientation in employee preferences, right-to-left languages, Safari and Firefox, and pop-up dialogs.
- **Limit worth restating:** this is appearance. The dashboard's coloured revenue blocks and charts are drawn by their own modules and were left as they are, and pages still reload on every action.

### 2026-10-06 — Dark mode, stylesheet consolidated, first dead-code removal, a broken move repaired

#### Back office dark mode

- **What:** a sun/moon button at the top right of the back office switches between a light and a dark look. The choice is remembered per browser; with no saved choice it follows the device's own setting. The sign-in page follows the same choice.
- **Where (extending existing code, rule 25):**
  - colours: `admin/themes/default/css/overrides.css`. Every colour in that file now comes from tokens (section 1, light); section 2 redefines the same tokens for dark. Section 9 covers the few things tokens cannot reach.
  - switch: `modules/salisbergguide/views/js/theme-toggle.js`, loaded for back office pages by the module's existing `addInterfaceAssets()`. No new module, hook or template.
- **Guide (rule 23):** Staff Guide, section 1: new step about the sun/moon icon.
- **Known gaps in dark mode:** the rich-text editor stays white (it is a separate embedded document), the dashboard's coloured revenue blocks keep their own colours, and pages drawn by modules not yet visited may show light areas. Add fixes to section 9 as they are found.

#### `overrides.css` consolidated (rule 27)

- The file had grown to 586 lines through five rounds of appended corrections, with early rules overridden by later ones. It was rewritten as one set of rules in numbered sections, with no superseded rules left. A final section, "Specificity catch-up", holds selectors that need `#content` in front to beat stock rules; that is a real need, not a leftover.
- The owner confirmed the layout is the point of the Krayin reference, not its colours, so the brand palette stays.

#### Dead code removed (rule 28)

| Removed | Evidence it was unused |
|---|---|
| `classes/Blowfish.php` | No reference anywhere in `classes`, `controllers`, `modules`, `override`, `admin`, `config`, `install`, `Core`, `Adapter` or the class index. Cookie encryption uses `PhpEncryption`. Not listed in `ConfigurationTest.php`. |
| `.travis.yml` | Travis CI is not used; nothing references the file. |
| `tests/` (7 files) | A PHPUnit scaffold whose config pointed at a `Unit` folder that does not exist; it contained no tests. |
| Mentions of the two above in `.dockerignore` and `Dockerfile` | They referred to files that no longer exist. |

- **Checked and kept, because they are in use:** `classes/AdminTab.php` (used by `admin/init.php`, `classes/functions.php` and `AdminSearchController`), `tools/pear` (listed in `ConfigurationTest.php`), `tools/random_compat` (required by `config/autoload.php`), and the other `tools/` libraries (each referenced once or more).
- **Next candidates, not yet examined:** superseded rules in `themes/hotel-reservation-theme/css/salisberg.css` (the navigation was styled twice), unused images under `img/`, and inherited shop features a hotel does not use (stock, warehouses, suppliers), which need a decision from the owner before anything is removed.

#### Repaired: `admin/functions.php` had been moved

- Commit `c6499de6` moved `admin/functions.php` to `classes/functions.php`. `admin/index.php` loads that file by path, so every back office page failed with a fatal error. The commit had been pushed to `salisberg-production`; the live site was not affected only because deployments were not completing at the time.
- The file was moved back unchanged (verified identical to the original). Rule 29 added.

#### Verification (local)

- Fresh build with the removals: back office sign-in works; dashboard, Orders, Room types, Customers, Book Now, Modules, Employees, Preferences and the Staff Guide return 200 with no errors; storefront home, room, sign-in and checkout return 200; a guest sign-in attempt (which exercises cookie encryption) works; no fatal errors and no mention of Blowfish in the log.
- Dark mode in a real browser: the button is added, a click switches the theme, the choice is saved and kept on the next page, no JavaScript errors. Screenshots reviewed in dark for the sign-in page, Orders, dashboard, the room type form, Book Now, Customers and the Staff Guide; and in light for Customers after the consolidation. A scan of six dark pages for leftover light backgrounds and dark text found three minor items, since fixed.
- **Not tested:** the remaining back office pages in dark mode, pop-up dialogs, Safari and Firefox, and the light theme page by page after the rewrite. Three light pages were re-checked by screenshot (Customers, dashboard, the room type form); that found the "YES" label on on/off switches unreadable, which was fixed and re-checked.

### 2026-10-06 — Website stylesheet: navigation rules merged (rule 27)

- **What:** `themes/hotel-reservation-theme/css/salisberg.css` had the desktop navigation styled twice (a first version, then a "minimal" version overriding it further down), with the signed-in account menu in a third place. They are now one section, "Navigation and account menu", holding only the values that were actually in effect. The Mobile Money / cash option styles moved from their own section to the end of "Checkout". 973 lines became 954. Stylesheet link bumped to `?v=8`.
- **No visual change intended.** One overridden value turned out never to have applied (`margin-right: 10px` on the menu lost to an earlier, more specific `18px`), so `18px` was kept.
- **Verification (local, real browser):** computed styles of every element in the header and menu were dumped before and after at 1440, 1100 and 390 wide and compared. All properties matched; the only differences were text widths of a few pixels on the first page loaded, which is the web font arriving at a different moment. Screenshot of the header at 1440 reviewed.
- **Not tested:** the signed-in header. The test guest account no longer exists in the local database, so the sign-in step of the comparison did not take effect and both runs measured the signed-out header.
- No guide change: nothing a guest, staff member or administrator does has changed.

### 2026-10-06 — Back office round 3: dashboard, top bar, menu fly-outs, buttons; "Bookings" menu; "Book Now"

All styling is in `admin/themes/default/css/overrides.css`, edited in place (rules 25 and 27). No new stylesheet or module.

#### Dashboard

- **Columns:** the third dashboard column has been empty since its vendor panels were removed, leaving a blank strip at the right. From 1200px wide the other two now share the full width (30% / 70%).
- **Things that ran outside their box, fixed:** the Occupancy figures (for example `381/71`), the info icon and refresh button sitting on top of the Occupancy title, chart axis labels cut off at the left edge, the Traffic Sources list overlapping its chart, and long amounts in the Performance tiles. The Sales tabs no longer use the old condensed capitals.
- Widget headings are now a row (title, then buttons at the right) so a long title is shortened with "…" and can no longer be covered.

#### Top bar

- **Account:** a circle with the employee's initials, then the name. The platform has no employee photo upload (`Employee::getImage()` returns the shop logo), so initials are used. This is one added `<span class="sb-avatar">` in `admin/themes/default/template/header.tpl`, a file already edited for branding.
- **Light/dark switch:** now a small switch showing a sun and a moon with a sliding knob (`modules/salisbergguide/views/js/theme-toggle.js`, same file as before).
- **Quick Access** is an outlined pill beside the notification icons; its menu and the account menu are rounded cards. An open menu no longer turns its button black.
- "My site" is aligned with the other items.

#### Side menu fly-outs

- **Problem reported by the owner:** the box of sub-pages that appears beside a menu section closed before it could be clicked.
- **Causes found by reproducing it with real pointer movement:**
  1. there was a 17px gap between the row and the box (an indent meant for the open section's list was also applied to the box);
  2. the stock script closes the box 50ms after the pointer touches another row, which happens whenever the pointer moves diagonally towards a lower entry;
  3. in windows shorter than 850px the stock theme does not list the open section's pages under it at all, so even the current section needed the fly-out.
- **Fix:** the box now starts under the row's right edge; an invisible wedge from the row to the full height of the box belongs to the box, so a diagonal move never leaves it; and the open section always lists its pages under it, at any window height. The box is a rounded card.
- No script was changed. Trade-off of the wedge: while a box is open, moving straight down along the right-hand part of the menu keeps that box open for a row or two before the next one takes over.

#### Buttons

- **Save / Save and stay / Cancel** at the bottom of forms: the stock large icon stacked over a small label is replaced by normal buttons with the icon beside the label. Save buttons are filled; Cancel is outlined.
- **Buttons beside the page title:** outlined, with the "Add new …" button filled. A stock rule had been keeping their bright blue border.
- **All other buttons:** one rounded shape, no shadows, a visible keyboard focus ring, and consistent colours by meaning through three new tokens (`--sb-success`, `--sb-danger`, `--sb-warning`). Sizes were deliberately left as stock so buttons still line up with the fields next to them.
- The vendor's "Recommendations" button and "Explore all QloApps addons" banner on the modules page are hidden (the earlier hide rule did not match this page's button).

#### "Orders" menu renamed "Bookings"

- **What:** the side menu section **Orders** and its first page **Orders** are now **Bookings** and **Bookings**.
- **How:** `renameMenuEntries()` in `modules/salisbergguide/salisbergguide.php`, called from the existing `ensureSetup()`; names are listed in `$menuLabels`. `MODULES_VERSION=6` so it runs on the next deploy. An entry is renamed only while it still has its stock name, so a name changed by hand in the back office is kept (rule 22).
- **Not renamed:** the page heading on that page still reads "Orders", as do "Order Messages", Preferences › Orders, and the word "order" inside pages and dashboard tiles. Those are text inside vendor controllers and templates.
- **Guides (rule 23):** Staff Guide sections 2, 4 and 11 and Admin Guide section 14 now say `Bookings › Bookings`; Staff Guide section 2 also explains the fly-out boxes; section 1 describes the new light/dark switch.

#### Website: "Make Booking" is now "Book Now"

- The button on the phone homepage. Done with a theme translation file, `themes/hotel-reservation-theme/modules/wkroomsearchblock/translations/en.php`, so the module's template is untouched (rule 11). **This path is git-ignored by upstream; the file must be added with `git add -f` (rule 19).**

#### Verification (local, real browser, light and dark)

- Screenshots reviewed: dashboard top to bottom, Quick Access and account menus open, the fly-out, the Orders and Modules pages, and the room type form footer. No PHP, template or JavaScript errors on any page visited.
- Fly-out: a scripted pointer moved in 30 small steps from a menu row diagonally to the fourth entry of its box. Before the fix the box was hidden for 25 of the 30 steps; after it, for none, at window heights of 720 and 900.
- Menu rename: ran `setup-modules.php`; the two entries changed and "Order Messages" and Preferences › Orders did not. The Staff Guide page showed the new text. Templates passed the Smarty syntax check.
- "Book Now" appears in the homepage HTML and "Make Booking" no longer does.
- **Not tested:** the buttons on every back office page (four pages surveyed), danger and success buttons in real use (no page visited showed one), pop-up dialogs, the fly-out near the bottom of a short window where the stock script moves the box upwards, widths under 1200px for the new dashboard columns, Safari and Firefox.
- **Mistake made and undone during this work:** `overrides.css` was stashed by accident mid-session and restored at once with `git stash pop`; nothing was lost.

### 2026-10-06 — Hotel Manager role; show or hide menu sections

Both live in the existing `modules/salisbergguide` module (rule 25). `MODULES_VERSION=7`.

#### Three roles

| Profile | Who | Can do |
|---|---|---|
| SuperAdmin | The developer team | Everything |
| Hotel Manager (new) | The person who runs the hotel | Everything about the hotel; nothing about how the system is installed or built |
| Hotel Staff | Front desk and reservations | Bookings, payments, guests; no deleting, no setup |

- **Hotel Manager can:** bookings including deleting, invoices and credit slips, Book Now, guests, addresses, messages and contact-form recipients; room types, service products, categories, features, bed types; cart rules, catalog price rules and Advanced Price Rules; hotel details and General Settings, the homepage content blocks; refund rules and requests; website pages (Preferences › CMS); staff accounts (Administration › Employees); Stats; both guides.
- **Hotel Manager cannot:** Modules and Services, Payment, Localization (currency, taxes), Preferences other than CMS, Advanced Parameters (email, backups, SQL), Profiles, Permissions, Channel Manager.
- **Why staff accounts are safe to hand over:** the platform itself stops anyone but a SuperAdmin from creating, editing or deleting a SuperAdmin, and only offers a non-SuperAdmin the other profiles. A manager has no access to Profiles or Permissions, so cannot widen their own rights.
- **How:** `$managerAccess` in `salisbergguide.php`. `installStaffProfile()` became `installProfile($name, $access)` and is called for both profiles (one function, not a copy). As before, an existing profile is never reset, so permissions adjusted by hand survive deploys.
- **Owner must:** in Administration › Employees, set the manager's account to Hotel Manager and keep SuperAdmin for the developer team's accounts only. Existing SuperAdmin accounts are not changed by this deploy.

#### Admin Guide is now for managers too

- A Hotel Manager can open the Admin Guide (`canReadAdminGuide()`; the controller still checks this itself and does not trust the menu permission).
- Sections a manager cannot act on (6 payment methods, 7 currency and taxes, 10 email, 11 maintenance, 12 backups, 15 menu sections, and the permissions, modules and SEO parts of 1 and 9) show a manager one line: "This is looked after by the developer team." A SuperAdmin sees the full text. Section numbers are the same for both.
- Section 1 now describes the three profiles. Rule 23 updated to match.

#### Show or hide menu sections (Admin Guide, new section 15)

- **What:** two Show/Hide switches, for **Channel Manager** and **Modules and Services**, visible to SuperAdmins only. Hiding removes the section from the left menu for everyone; nothing is uninstalled or disabled, and the pages still open by link for those with permission (the guide's own links keep working).
- **Why in the guide page and not the module's Configure page:** that page is reached through Modules and Services, which is one of the things being hidden.
- **How:** `$menuToggles`, `getMenuToggles()` and `setMenuVisible()` in `salisbergguide.php` set the menu entry's `active` flag; the form posts to `AdminSalisbergAdminGuideController::postProcess()`, which acts only for a SuperAdmin and only on the listed entries. The choice is stored in the database and survives deploys. Default: both shown, as before.
- **About the Channel Manager:** the bundled module only connects to a separate, paid channel manager service that syncs rooms and prices with booking sites. It does nothing until that service is bought and set up.

#### Verification (local, real browser)

- **As a test Hotel Manager:** the menu shows Dashboard, Catalog, Bookings, Customers, Manage Discounts, Hotel Reservation System, Preferences, Administration, Stats, Guides. Eleven permitted pages opened. Twelve others (Modules, Payment, Preferences, Maintenance, Currencies, E-mail, DB Backup, Profiles, Permissions, Channel Manager, Themes, SQL Manager) were refused when requested directly with a valid token. Opening the SuperAdmin's employee record showed no form; deleting it was refused ("You cannot disable or delete the administrator account"); a new employee could only be given Hotel Staff or Hotel Manager. The Admin Guide opened with six developer-team notes, no switches and no Permissions instructions.
- **As SuperAdmin:** the switches appear; hiding both removed both menu sections and the Modules page still opened by link; showing both restored them. A request posted with a wrong token changed nothing.
- **Not tested:** every action a manager can reach (for example saving a price rule or deleting a booking as manager); the homepage content block pages as manager; Hotel Staff after this change beyond the module's own setup run; any of this on the live server.
- A test employee `manager.test@example.com` exists only in the local database.

### 2026-10-06 — Dark dashboard contrast, softer light theme, Advanced Parameters documented, a config check fixed

#### Look (`admin/themes/default/css/overrides.css`)

- **Dark mode:** the titles on the pastel Performance tiles (Average Daily Rate and the other seven) were light text on a light tile and could not be read. They are dark in both themes now, since the tiles are pastel in both.
- **Light mode was too bright.** The light tokens were toned down: cards, bars, menus and fields from pure white to an off-white (`--sb-card: #f7f7f5`), the page background a step darker (`--sb-canvas: #e8eaed`), and borders slightly stronger so cards still read as cards. Change them in section 1 of the file. The guide pages now take their card colour from the same token in both themes (the dark-only rule was generalised, not duplicated).
- The seven figure tiles at the top of the dashboard now use the card colour in both themes (follow-up, 2026-10-07: the dark-only rule was generalised).

#### Wording

- Top bar: "My site" is now **Go to Website** (`admin/themes/default/template/header.tpl`).
- Advanced Parameters › Configuration Information: the vendor line about "our bug tracker or forum" now says what the page is and to send it to the developer team, and points to the guide (`admin/themes/default/template/controllers/information/helpers/view/view.tpl`). Both are admin theme templates, which cannot be overridden (the accepted exception to rule 11).

#### Bug found and fixed: "Some QloApps files are missing from your server"

- Configuration Information › Check your configuration showed **Required parameters: Please fix the following error(s) … (/cache/smarty/compile/index.php)**.
- **Cause:** `.dockerignore` excluded everything under `cache/smarty/compile/` and `cache/smarty/cache/`, including the two placeholder `index.php` files the platform's self-check expects. They were never in the image, so this has been showing on the live site since the first deploy.
- **Fix:** `.dockerignore` keeps those two files. Harmless otherwise: the folders worked without them.

#### Admin Guide, new section 16 "Advanced Parameters, page by page" (rule 23)

- For SuperAdmins; a Hotel Manager sees the standard developer-team note.
- A table of the eight pages (Configuration Information, Performance, E-mail, CSV Import, DB Backup, SQL Manager, Logs, Webservice) with what each is for and what to do there, then four subsections:
  - **Configuration Information:** what each box means; that "PHP mail()" means email is not set up; that both checks should say OK; and that **List of changed files is always long on this site and is not a fault**, because the site is a customised version.
  - **Performance:** which settings to keep and why (Debug mode switches would turn off the Salisberg modules and fixes; CCC is untested with this design); Clear cache is always safe.
  - **CSV Import:** back up first, use the sample files, try two or three rows first; an existing ID is replaced.
  - **Webservice:** what it is (access for other programs by key, with no sign-in), why it stays off, and how to issue a narrowly scoped key if an integration is ever added.
- Page, button and box names were read from the rendered pages before writing.

#### Verification (local, image rebuilt, real browser)

- Configuration Information after the rebuild: **Required parameters: OK, Optional parameters: OK**; the new intro line is shown.
- Top bar reads "Go to Website".
- Admin Guide section 16 renders with its table and four subsections, no template errors.
- Screenshots reviewed: light dashboard with the softer palette, dark Performance tiles (titles readable), Configuration Information, and the new guide section.
- **Not tested:** the softer light palette on pages other than the dashboard, Configuration Information and the guide; the CSV import, SQL Manager and webservice steps were written from what the pages show and were not carried out; nothing on the live server.
- **Follow-up, 2026-10-07:** the stock "working" spinner, which appeared as a grey square over the top-left corner of the logo while a page loaded data, now sits as a small icon at the right end of the top bar. Checked by screenshot in both themes.

### 2026-10-07 — Vendor name removed from on-screen text

The owner asked for every "QloApps" reference that can be removed without risk of breaking anything.

#### What was done

- **One override instead of 38 file edits:** `override/classes/Translate.php`. A search found the vendor name in 67 pieces of on-screen text across 38 vendor files (back office templates, admin controllers and module descriptions). All of that text passes through three functions of the platform's `Translate` class, so the override replaces the name with "Salisberg" in their output. No vendor file was edited (rule 11), and the change is undone by deleting the one file.
  - Examples: "Enable Salisberg's webservice", "Salisberg version:", "Disable non Salisberg modules", "Some Salisberg files are missing from your server."
  - A name that is part of a web address (`qloapps.com/…`) is left alone, so links keep working.
- **Vendor store page hidden:** Modules and Services › Modules Catalog loads the vendor's marketplace from their website (136 mentions on that one page). `hideVendorMenus()` in `modules/salisbergguide/salisbergguide.php` takes it out of the menu on every deploy (`$vendorMenus`; `MODULES_VERSION=8`).
- **Sample guest retired:** the installer's sample customer `pub@qloapps.com` ("John Doe") has a publicly known address and password. The content step in `docker/entrypoint.sh` (`CONTENT_VERSION=2`) marks it deleted and inactive, using the platform's own flags, unless it has a booking. The step logs how many accounts it retired (rule 22).

#### Deliberately left, because changing them can break things or is not ours to change

| Where | Why it stays |
|---|---|
| Licence headers and copyright notices in about 900 files | Required by the OSL-3.0 licence (rule 14) |
| Class, file and folder names (`AdminQloappsChannelManagerConnector`, `modules/qlo*`, `qloapps@2x.png`), the `qlo_` table prefix | Code finds these by name (rule 12). They appear in web addresses and page source, not in text people read |
| JavaScript event names such as `QloApps:updateRoomOccupancy` | The booking form on the website depends on them |
| Links to `qloapps.com` (search page shortcuts, module store links) | They are addresses; the override leaves them so they still work |
| "QLOAPPS.COM" in the dashboard's Traffic Sources | Sample figures shown only while the dashboard's Demo mode is on |
| `PATCHES.md`, `README.md`, this file | They record where the code and the security fixes came from |

#### Honest caveat

- The replacement is by word, so a few vendor sentences now read oddly or say something that was written about the vendor: for example the backup page's disclaimer ("Salisberg is not responsible for your database…") and the module pages' references to "Salisberg Addons", a store that does not exist. These are on SuperAdmin-only pages. If any of them matters, reword that sentence in its template.

#### Verification (local, image rebuilt, real browser)

- Scanned the visible text, tooltips and image descriptions of 26 back office pages for the vendor name. Before the Modules Catalog was hidden and the pattern corrected: 139 mentions (136 on the catalog page). After: **one**, the demo-mode traffic source. Every page loaded without errors.
- A first version of the pattern skipped a name followed by a full stop ("…inside QloApps."), mistaking it for a web address. Found by the scan and corrected; the pattern was then checked against seven sample strings.
- Entrypoint log: `Content v2 applied (… sample guest accounts retired: 1)`; the account is inactive and marked deleted and no longer appears under Customers. The Modules Catalog entry is inactive.
- The website homepage has no visible mention (unchanged from before).
- **Not tested:** PDFs (invoices) and every module's own configuration page; the pages of modules that are disabled; anything on the live server. On the live site the sample guest is only retired if it has no bookings.

### 2026-10-07 — Contact form no longer takes file uploads

- **What:** the "Attach File" field is gone from the website's Contact page; the form takes messages only. The owner said it is not needed.
- **How:**
  - The platform already has a switch for this (Customers › Customer Service, Contact options, "Allow file uploading"). The content step in `docker/entrypoint.sh` sets it to No on the next deploy and logs whether it changed anything. `CONTENT_VERSION=3` (2 was never deployed).
  - `override/controllers/front/ContactController.php`: with the switch off, the stock controller only hides the field and would still store a file posted to it directly. The override drops any such file first. An override, not a core edit (rule 11).
- **Guide (rule 23):** Admin Guide, section 9 "Website content": new row saying the form takes messages only and where the switch is.
- **Verification (local, image rebuilt):** log showed `contact form file upload switched off: 1`; the Contact page has no file field (screenshot and field list); a message posted directly with a file attached was accepted as a message, stored with no file name, and nothing was written to the upload folder.
- **Not tested:** switching the option back on; the live server. Staff replies from the back office can still attach files; that is a separate, staff-only feature and was left alone.

### 2026-10-07 — CHANGELOG.md, a What's New page with its own permission, dark-mode list fixes

#### CHANGELOG.md (new, repo root)

- Every change so far, newest first, in plain words under Added / Changed / Fixed / Security / Removed. It is the short record; this section stays the detailed one. `CHANGELOG.txt` is the vendor's own history and is left alone.
- Removed from the web root by the `Dockerfile`, like `CHANGELOG.txt`, so it is not downloadable from the website.
- **Rule 30 added:** every change gets a line in `CHANGELOG.md`, and a line on the What's New page if a back office user will notice it.

#### What's New page (Guides › What's New)

- **What:** a read-only page listing recent changes by date, written for the people using the back office. Each reader sees only the items for their role: everyone, then "For the hotel manager", then "For the developer team".
- **Where (rule 25, existing module):** `modules/salisbergguide`: `controllers/admin/AdminSalisbergWhatsNewController.php` (same shape as the two guide controllers) and `views/templates/admin/whats_new.tpl`. It also appears as a card on the Guides page for those who may open it. `MODULES_VERSION=9`.
- **Tied to a right that can be given or taken away:** it is an ordinary menu page, so Administration › Permissions has a "What's New" row per profile. Unlike the guides, the page has no extra check of its own and the right is **not** re-granted on deploy: Hotel Staff and Hotel Manager receive it once, when the page is first created (`grantWhatsNew()`), and a later change in Permissions is kept.
- **Guides (rule 23):** Staff Guide section 2 lists the page in the menu table; Admin Guide section 1 ("Change what a profile can do") says how to give or remove it.
- **Why a template and not a page that reads `CHANGELOG.md`:** the changelog includes security and infrastructure entries that are not for front desk staff, and the page has to show different items to different roles.

#### Dark mode: lists

- The owner reported the E-mail (SMTP) and Logs pages looking wrong in dark mode. Found and fixed in `overrides.css`: an empty list showed a white block ("No records found"), the row of search boxes under a list's column titles was pale blue, and the lines between rows were bright white. These are shared list parts, so the fix applies to every list page.

#### Verification (local, image rebuilt, real browser)

- **What's New:** as a test Hotel Manager the page opens with the everyone and manager items and no developer items; as SuperAdmin all three groups show. Menu entry and Guides card present for both. No template errors.
- **The right:** with View removed for Hotel Staff and Hotel Manager, the setup step was run again and the right stayed removed; the manager was refused the page, and its card and menu entry were gone.
- `CHANGELOG.md` requested over the web returns 404.
- **Dark mode:** a scan for light backgrounds and dark text on E-mail, Logs, Orders, Customers, SQL Manager, Webservice, Cart Rules and Employees found only the small "x" on dismissible notices. Screenshots of E-mail and Logs reviewed.
- **Not tested:** What's New as Hotel Staff (same code path as the manager, with fewer items); the live server.

### 2026-10-07 — Website footer corrected, larger side menu text, README rewritten, LICENSE.md restored

#### Website footer

- **"Payment accepted" was wrong, not just old:** it showed the installer's Visa, American Express, MasterCard and PayPal logos. The hotel takes cash and Mobile Money. It now shows two badges, **Cash** and **Mobile Money**, in the site's colours.
  - Source: `docker/branding/payment-badges.html`; output `docker/branding/pay-cash.jpg` and `pay-momo.jpg` (384x240). The steps to regenerate are in the HTML file's header. They are generic badges: no mobile network's logo is used.
  - The module keeps its pictures in a persisted folder and their names in the database, so replacing files in the repo alone would change nothing on an existing site. The content step in `docker/entrypoint.sh` (`CONTENT_VERSION=4`) copies the two files over pictures 1 and 2, renames those entries, and switches MasterCard and PayPal off. It only does this while entries 1 and 2 are still the installer's "Visa" and "American Express" (rule 22), and logs which it did.
- **Explore links:** Home, Our Properties, Interior and Contact Us, which left the desktop menu bar on 2026-10-06, are now listed in the footer (same step; only links still carrying those names). The list is shown in two columns.
- **"Follow us on"** had a heading and nothing under it, because no social links are set. The column is hidden until at least one link is entered (Modules › Social networking block), and the remaining columns share the width.
- **Copyright line** read "© 2010-2026": 2010 is the installer's sample founding year. It is cleared, so the line reads "© Salisberg Hotels. All rights reserved." until the real year is entered in Hotel Reservation System › General Settings.
- Styles in `salisberg.css` (footer section), link bumped to `?v=9`.

#### Back office

- Side menu text one step larger: section names 14px to 15px, sub-pages 13px to 14px (`overrides.css`, section 5).

#### Documents

- **`README.md` rewritten.** It still described "StayFlow" as a generic product with installation requirements for shared hosting. It now says what Salisberg is, where things are, how to run and deploy it, the three roles, and credits QloApps and its licence.
- **`LICENSE.md` restored.** It had been deleted in commit `892b0ae0`. The OSL-3.0 licence expects the licence text to ship with the code, and the README links to it. Restored unchanged from the commit before.

#### Verification (local, image rebuilt, real browser)

- Log: `Content v4 applied (… footer: payment badges replaced, links added: 4)`. Database afterwards: Cash and Mobile Money active, MasterCard and PayPal inactive; the four links marked for the footer; founding year empty.
- Footer screenshots at 1440 and 390 wide: two badges, three columns, nine Explore links in two columns, no "Follow us on", copyright line without a year.
- **Not tested:** the badge height was raised from 40px to 48px after the screenshots and not re-captured; the larger side menu text was not re-captured either (a two-value change); the footer with social links entered; the live server, where the step will act only if the sample entries are still in place.
- **Left for the owner:** the Explore list still includes the installer's "Secure Payment" page, whose text is the vendor's sample wording. Rewrite or unpublish it in Preferences › CMS.

### 2026-10-07 — Website dark mode; back office sign-in page, tooltips and pop-ups; upgrade plan

#### Website: light/dark switch

- **What:** a sun/moon switch in the website header (before the cart, on computers and phones). The site starts **light**; a visitor who chooses dark keeps it on that device. The website and the back office remember their choices separately.
- **Where (rule 25, existing files):**
  - `modules/salisbergguide/views/js/theme-toggle.js`: the same script now serves both. On the website the module sets `sbThemeScope = 'site'` (`addInterfaceAssets()`), which gives it its own saved choice, a light default, and a place in the website header.
  - `themes/hotel-reservation-theme/header.tpl`: one inline line applies a saved "dark" before the page is drawn, so there is no flash of the light page.
  - `themes/hotel-reservation-theme/css/salisberg.css` (`?v=10`): three new tokens (`--sb-page`, `--sb-surface`, `--sb-heading`) replaced 13 hard-coded white backgrounds and 18 uses of the brand green as text colour; a dark set of token values follows the light set; a "Dark mode" section at the end covers colours written into the stock theme's own stylesheets.
- **Logo:** the logo file is dark ink on white. In dark mode it is inverted and blended so the white drops out, instead of sitting in a white box. No second image file.
- **How it was built:** a script listed, page by page, every large light background and every piece of text too close in brightness to what is behind it; rules were added until the list was empty.
- **Guide (rule 23):** nothing for staff to do. Listed on the What's New page for managers.

#### Back office sign-in page

- The owner asked for it to be fixed. It showed the logo, a version number, a second round logo overlapping the form, the shop name again, and an outer shadowed column; in dark mode the logo sat on a white plate and the round logo's dark parts vanished.
- Now: the logo, then one card with the form, centred on the page, at any width. Section 10 of `overrides.css` was rewritten, not added to (rule 27). The dark-mode logo uses the same invert-and-blend as the website.

#### Tooltips and pop-up windows (back office)

- **Tooltips** were a square grey box with 20px of padding, an arrow of a different colour in dark mode, and could slide under the top bar and side menu. Now: one dark rounded bubble in both themes, arrow matching, drawn above everything. Two tokens, `--sb-tip` and `--sb-tip-line`.
- **Pop-up windows:** bordered card with a shadow, titled header, tinted footer, and a close "x" that is visible in dark mode (it was black on dark).
- The earlier dark-only tooltip rule was removed, not left underneath.
- **Side menu:** the text size increase earlier today cut off three long names ("Modules and Servi…"). The menu is 264px wide instead of 240px (`--sb-side`), and all names fit.

#### Upgrade plan

- The owner asked for a plan to upgrade language and stack versions and styles. Written to `audit/upgrade-plan.md` (private). Summary: PHP 8.1 and MySQL 8.0 are both past end of support and are the priority (targets 8.3 then 8.4, and 8.4 LTS, both within what the platform supports); then drop-in updates of the bundled libraries (TinyMCE, Bootstrap scripts, jQuery 1.x, Smarty); then style tidy-up. A staging copy and off-server backups come first. **Nothing has been upgraded yet.**

#### Verification (local, real browser)

- **Website dark mode:** home, a room page, search results, contact, sign-in, About Us, the empty cart and Our Properties scanned in dark: no large light areas or unreadable text left. Screenshots of home, room, search and contact reviewed. Light mode re-checked by screenshot (home, room): unchanged. The switch was clicked: the theme changed, was saved, was still dark on the next page, and switched back. Phone width: switch present and working.
- **Sign-in page:** screenshots in light and dark at 1440 and 390 wide. A first version hid the logo by mistake (the selector for the version number also matched the logo's heading); caught on the screenshot and corrected.
- **Tooltips and pop-ups:** hovered a real tooltip on the dashboard and opened a real pop-up on the Modules page, in both themes; colours read back from the page and screenshots reviewed.
- **Not tested:** website dark mode on signed-in guest pages (My account, bookings), checkout with a room in the cart, the booking and cart pop-ups, the date picker and the phone menu panel; Safari and Firefox, including the logo blend; pop-ups other than the one opened; the live server.

### 2026-10-07 — Side menu arrows; website dark mode on the remaining pages

#### Back office side menu: fold and unfold with an arrow

- **Asked by the owner:** the boxes of pages that popped out when the pointer passed over the menu looked stacked; a small arrow that shows the pages when clicked would be better.
- **What:** every menu section with pages under it has a small arrow at the right of its row. Clicking it lists the pages under the section; clicking again folds them away. Several sections can be open at once. The section you are in starts open. Clicking a section's name still opens its first page. Nothing appears on hover any more.
- **Where (rule 25):** `modules/salisbergguide/views/js/side-menu.js` (new file in the folder for small interface scripts, loaded for back office pages by `addInterfaceAssets()`), and section 5 of `overrides.css`.
- **The fly-out rules from 2026-10-06 were removed, not left underneath (rule 27):** the card styling and the invisible wedge are gone.
- **Why the menu looked "stacked":** in windows shorter than 850px (most laptops) the stock theme switches to 28px rows with 12px text, squeezes the page lists so their lines overlap, and draws a pointer for its hover box. Those rules were overriding ours. The menu now keeps one size at every window height, and scrolls on its own when it is longer than the window (it could not scroll before, so its lower entries could be unreachable).
- The menu is 288px wide (was 264px) so the longest name still fits beside its arrow.
- Open sections are not remembered from page to page; each page starts with only the current section open.
- **Guide (rule 23):** Staff Guide section 2 now describes the arrows instead of the hover boxes.

#### Website dark mode: pages not covered before

- Tested the pages listed as untested on the previous entry, signed in as a test guest, and fixed what was found (`salisberg.css`, `?v=11`):
  - **Breadcrumb** on account pages: light background with near-invisible text.
  - **Drop-down boxes** (date of birth, state): the stock picture of a box and arrow was repeating inside our taller box. This was broken in light mode too. The picture is replaced by a drawn arrow, in both themes.
  - **Date picker:** white calendar.
  - **Checkout:** room name, amenity chips and the total box.
  - The "Done" button in the guests picker was stock blue; it uses the brand colour now, in both themes.
- Pages that needed no change: My account, booking history, personal information, addresses (after the fixes above), the account menu, the guests picker, the phone menu panel.

#### Verification (local, real browser)

- **Arrows:** 11 arrows on 11 sections. Hovering three rows opened nothing. Real clicks: an arrow opened its list and stayed on the same page; a second section opened alongside it; a second click folded the first; the current section could be folded and reopened; a page link inside an opened list navigated correctly. Run at a 760px-high window, the size where the problem showed. Screenshots in light and dark reviewed. At that height the menu scrolls (858px of content in a 700px area).
- **Website:** date picker, account pages, account menu, search results with the guests picker open, checkout with a room in the cart, and the phone menu, all in dark. The scan reported one item left, the room name on checkout, since given a stronger rule.
- **Not tested:** that last room-name fix and the "Done" button colour by screenshot; the payment step of checkout in dark (it needs the guest details step completed); the arrows by keyboard; Safari and Firefox; the live server.
- A test guest `guest.test@example.com` exists only in the local database.

### 2026-10-07 — Guides unreadable in dark mode (regression); menu scroll bar; larger menu text

- **Guides in dark mode:** the owner reported that every page under Guides had poor contrast in dark mode. Light text was sitting on white cards (contrast about 1.4:1). **Cause: a mistake of ours earlier the same day.** When the guide card colour was changed from a dark-only rule to one rule for both themes, the selector lost its `html[data-sb-theme="dark"]` prefix and with it the weight it needed to beat the guide's own stylesheet, which loads after `overrides.css`. Fixed by putting `#content` in front of that rule. Table headings and the "Open …" links were also below the readable threshold in dark and were lightened.
  - **Lesson:** when a themed rule is generalised, check it still wins. A measured check is now part of the method: a script computes the contrast ratio of every piece of text on the page against what is behind it and lists anything under 4.5:1.
- **Side menu scroll bar:** the menu scrolls when it is longer than the window (added with the arrows earlier today), and now shows a thin scroll bar in the menu's own colours (`scrollbar-width`, `scrollbar-color`).
- **Side menu text** one more step larger at the owner's request: section names 16px, pages 15px, rows 42px. The menu is 304px wide so names still fit beside their arrows.
- **Verification (local, real browser):** the contrast script reported no text under 4.5:1 on Guides, Staff Guide, Admin Guide and What's New in dark mode (before: 3 to 190 failing items per page). Staff Guide screenshot reviewed. With three sections opened in a 760px-high window the menu held 1055px of content in 700px, scrolled, and the last entry could be reached.
- **Not tested:** the scroll bar's appearance. The test browser draws scroll bars as overlays, so the bar itself was not seen; only that the menu scrolls and the styles are applied. The guides in light mode were not re-captured after this change (the rule sets the same card colour as before).

### 2026-10-07 — Upgrade work started; printable reports; Stats pages

Work is on `develop`. `main`, `develop` and `salisberg-production` were brought to the same state first (the owner pushed).

#### Upgrade step 0: a repeatable check (`docker/smoke-test.sh`, new)

- One command, `bash docker/smoke-test.sh`, replaces the ad hoc page checks used so far. Against a running stack it confirms: ten website pages load without error text; six files that must stay private are not served; an administrator can sign in; the dashboard and nine back office pages load (reached through the dashboard's own menu links, so their tokens are valid); and the app log has no new PHP errors. It prints the PHP and MySQL versions it ran against, and exits non-zero if anything failed. It saves nothing and makes no booking.
- **Baseline recorded on the current stack (PHP 8.1.34, MySQL 8.0.46): 29 passed, 0 failed.** Every upgrade step below is judged against that.
- It is a first check, not a full test (rule 15 still applies).

#### Upgrade step 1: PHP 8.3 — done (see the follow-up at the end of this entry)

- `Dockerfile` now takes the PHP version as a build argument, `ARG PHP_VERSION=8.1`, passed from `docker-compose.yml` (`PHP_VERSION`, documented in `.env.example`). **The default is still 8.1**, so a deploy from this code builds exactly what it built before.
- **Why it is not switched yet:** the `php:8.3-apache` base image had not finished downloading on the development machine's connection when this entry was written, so nothing has been built or run on 8.3. Per rule 15 the default is not changed until it has.
- **To finish:** `PHP_VERSION=8.3 docker compose up -d --build`, then `bash docker/smoke-test.sh`, then click through bookings, the room editor, a PDF (invoice and Bookings › Reports) and the E-mail test, reading the app log for new warnings. If clean, change the default in the `Dockerfile` to 8.3.

#### Upgrade step 2: MySQL 8.4 — made a deliberate switch, NOT yet done

- `docker-compose.yml`: `image: mysql:${MYSQL_VERSION:-8.0}`. **The default is still 8.0.**
- **Why a setting and not a code change:** the move is one-way. Once 8.4 has opened the data files, 8.0 cannot read them again; the only way back is a restored backup. As a plain edit to the compose file it would have upgraded the production database on the next routine deploy. As a setting, it happens only when someone adds `MYSQL_VERSION=8.4` in Coolify on purpose.
- **Before setting it anywhere real:** a backup restored successfully somewhere else (rule 8); off-server backup copies in place; the same change run first on a staging copy with a copy of the live data; `docker/smoke-test.sh` passing afterwards.

#### Stylesheets

- A small checker (duplicate selectors in the same context, a property set twice in one rule, empty rules) was run over our four stylesheets. It found three selectors declared twice (two in `overrides.css`, one in `salisberg.css`); each pair was merged into one rule (rule 27). No repeated properties, no empty rules. `!important` count for the record: 64 in `overrides.css`, 45 in `salisberg.css`; nearly all are there to beat stock rules that themselves use `!important` or inline styles, and were left.
- **Not done:** the bundled libraries (jQuery, Bootstrap scripts, TinyMCE, Smarty). Each is its own step with its own testing; see `audit/upgrade-plan.md`.

#### Printable reports (new module `salisbergreports`)

- **What:** a page at **Bookings › Reports**. Choose a report and a period, see it on screen, download it as a PDF.
  - **Reports:** Bookings (every booking made in the period); Arrivals and departures (rooms checking in and out, a daily sheet for the desk and housekeeping); Income by day.
  - **Periods:** Today, This week, This month, Last month, or Choose dates with From and To. At most one year.
  - **PDF:** landscape A4, the invoice logo, report title and period at the top, a row of summary figures, the table with a repeating header, and a footer with when and by whom it was printed. Built with the platform's own `PDFGenerator` (TCPDF), the same engine as invoices.
- **Why a new module (rule 25):** nothing existing fits. `salisbergguide` holds help pages, roles and small interface scripts; `salisbergpay` is payments. Reports read booking data and produce documents, which is a separate concern with its own page, templates and permission. It follows the same patterns as the other two (menu entry created in `ensureSetup()`, installed by `docker/setup-modules.php`, `sb-` naming). `MODULES_VERSION=10`.
- **Permission:** an ordinary menu page, so Administration › Permissions has a "Reports" row. The Hotel Manager is given it once, when the page is first created; Hotel Staff are not (it shows income). Either can be changed in Permissions and the change is kept.
- **Rules followed:** totals leave out cancelled, refunded and payment-error bookings, but the Bookings report still lists them. Request values are validated before use (report and period against fixed lists; dates against a pattern and `Validate::isDate`; reversed dates are swapped; anything else falls back to today), dates go through `pSQL()`, and every cell is escaped in the templates (rule 21). The page saves nothing.
- **To add a report:** add its name in `getReports()` and a `build<Name>()` method returning the same shape; the screen and PDF templates render any report of that shape.
- **Guide (rule 23):** Admin Guide section 14 "Reports" now starts with "Printable reports (PDF)". Listed on What's New for managers. Added to the Hotel Manager's page list in `salisbergguide.php`.

#### Stats pages ran off the right edge

- Reported by the owner. Measured all 20 Stats reports at a 1366px-wide window: four (the Stats home, Stats Dashboard, Best hotels, Best-selling room types) had tables up to 250px wider than the space beside the report menu, which made the whole page scroll sideways. Each such table now scrolls inside its own box (`overrides.css`, scoped to the Stats page).

#### Verification (local; everything below ran on PHP 8.1 and MySQL 8.0)

- Image rebuilt from the working tree with all of the above: `setup-modules` logged the reports module installed and its page in place; `docker/smoke-test.sh` 29 of 29.
- **Reports:** five test bookings were inserted into the local database (one paid, one unpaid with two rooms, one part-paid, one cancelled, with stays around today). On screen: Bookings for the month showed 2 bookings, 3 rooms, GH₵5,580 value, GH₵2,080 paid, GH₵3,500 to pay, 1 cancelled, which matches the test data by hand; Arrivals and departures for the week showed 3 and 4 with the cancelled room left out; Income by day agreed with Bookings. Reversed dates were swapped; a script tag and an impossible date in the address fell back to today with no error. All three PDFs downloaded as valid PDF files with sensible names; the Bookings and Arrivals PDFs were opened and read. Column widths were adjusted after that reading (amounts and "Checked in" were wrapping) and the PDFs were not re-opened afterwards.
- **Stats:** after the fix none of the 20 reports makes the page scroll sideways.
- **Not tested:** anything on PHP 8.3 or MySQL 8.4; reports as a Hotel Manager (the permission row was confirmed in the database, the page was only opened as SuperAdmin); reports with real bookings made through the website, with refunds, or with hundreds of rows across several PDF pages; the PDF in dark mode is not applicable (PDFs are always light); the live server.
- Local test data: bookings with references starting `TEST` exist only in the local database.

#### Follow-up, same day: PHP 8.3 built, tested and made the default

- Once the base image had downloaded, the image was built on PHP 8.3.35 with the same extensions (gd, pdo_mysql, mysqli, soap, zip, intl, mbstring, opcache all loaded).
- **Checks run on 8.3:**
  - `docker/smoke-test.sh`: 29 of 29, no PHP errors or warnings logged.
  - Every one of the 3,076 PHP files in the image compiled with all error reporting on: no parse errors, no compile-time deprecation notices.
  - In a real browser, with the app log read afterwards: Bookings › Reports on screen and all three PDFs (TCPDF, the same engine as invoices); guest sign-in (bcrypt password check), account pages, adding a room to the cart from search results, checkout; the contact form reaching its duplicate-message check (a database write path); Hotel Manager sign-in and menu. The log had no PHP errors, warnings or deprecation notices.
  - The `backup` service, built from the same Dockerfile, starts on 8.3; its database dump (MariaDB client, unchanged) produced a dump of the same size as before.
- **Made the default:** `ARG PHP_VERSION=8.3` in the `Dockerfile` and `${PHP_VERSION:-8.3}` in `docker-compose.yml`, then rebuilt with no override and re-ran `docker/smoke-test.sh`: 29 of 29 on PHP 8.3.35. Sections 1 and 3 updated.
- **To go back to 8.1 if the live site shows a problem:** set `PHP_VERSION=8.1` in Coolify and redeploy. Nothing in the data depends on the PHP version.
- **Not tested on 8.3:** a real invoice PDF (no real order with an invoice exists locally); sending email (no SMTP set up); the live server. Watch the `app` log after the first deploy.

### 2026-10-07 — Bundled libraries updated; bank transfer; module versions checked

#### Upgrade step 3: bundled libraries

Each moved to the last release of the line it was already on, which are fixes rather than redesigns. All three old versions dated from 2014.

| Library | From | To | How it was swapped |
|---|---|---|---|
| jQuery | 1.11.0 | 1.12.4 | New file `js/jquery/jquery-1.12.4.min.js`; the version is chosen in the new `config/defines_custom.inc.php` |
| Bootstrap scripts (back office) | 3.1.1 | 3.4.1 | `admin/themes/default/js/vendor/bootstrap.min.js` replaced |
| TinyMCE (text editor) | 4.0.16 | 4.9.11 | `js/tiny_mce/tinymce.min.js`, `themes/modern/theme.min.js` and the 38 plugins that exist in both versions replaced |

- **Where the files came from:** the official npm registry (`jquery`, `bootstrap`, `tinymce` packages at those exact versions). Each download was checked against the SHA-512 the registry publishes for it before anything was copied.
- **jQuery without editing a vendor file:** `config/config.inc.php` loads `config/defines_custom.inc.php`, when it exists, before its own defaults, and each default is wrapped in `if (!defined(...))`. Defining `_PS_JQUERY_VERSION_` there switches every page to the new file. To go back, delete that line.
  - The old `jquery-1.11.0.min.js` is **kept**: `classes/controller/AdminController.php` still names it for one fallback page (rule 28: not dead). The website's maintenance page (`themes/hotel-reservation-theme/maintenance.tpl`) was pointed at the new file.
  - `jquery-migrate-1.2.1` is unchanged; its file name is written into `classes/Media.php`.
- **TinyMCE:** the platform's own toolbar look (`skins/prestashop`), its language files, and three old plugin folders that 4.9.11 no longer ships (`example`, `example_dependency`, `layer`; none is loaded by the editor's configuration) were left as they are. The editor configuration in `js/admin/tinymce.inc.js` was not touched.
- **Not updated:** the Bootstrap styles (compiled into the stock theme's 9,000-line stylesheet), `admin/filemanager`'s own older Bootstrap, Smarty (4.5.5 is on a supported line), and jQuery beyond 1.x (see `audit/upgrade-plan.md`).
- These are vendor files replaced with newer vendor files. Expect them in the diff when the next QloApps release is adopted; if that release ships the same or newer versions, take theirs.

#### Bank transfer (Salisberg Pay)

- **Asked by the owner:** bank and card payments can be offered.
- **Bank transfer:** added to the existing `modules/salisbergpay` as a third method, built exactly like Mobile Money (rules 25 and 26), instead of switching the stock `bankwire` module back on. That module shows itself to guests even with no account details entered, uses its own order state, and sends emails in the vendor's wording.
  - Settings (Salisberg Pay › Configure): Bank transfer on/off, Bank, Branch, Account name, Account number, an optional note. The account number is validated (letters, digits, spaces, hyphens, 5 to 34 characters); the others follow the same rules as the Mobile Money fields.
  - Hidden from guests until an account number is saved. The booking is created as **Awaiting payment** with the method "Bank transfer"; staff record the money as for the other methods.
  - The three lists of setting names in the module became one (`$settings`). `ensureSettings()` creates any setting that does not exist yet and is called from `docker/setup-modules.php`, which is how the new settings reach the live install (`MODULES_VERSION=11`). Bank transfer starts switched on, and therefore hidden until the account number is entered.
- **Card payments: not done.** They need an account with a payment company and its API keys (in Ghana: Paystack, Hubtel or Flutterwave; each also handles Mobile Money automatically). Connecting one is a new piece of work once the owner has chosen a provider and opened the account. The bundled PayPal module was left uninstalled.
- **Owner must:** enter the bank details in Salisberg Pay settings. Note the form will not save while Mobile Money is switched on with no number: enter the Mobile Money number as well, or switch Mobile Money off.
- **Guides (rule 23):** Staff Guide section 5 is now "Record a cash, Mobile Money or bank transfer payment" with a Bank transfer subsection, and sections 3 and 10 mention it; Admin Guide section 6 covers the bank fields and says why cards are not offered. What's New and `CHANGELOG.md` updated.
- **Not changed:** the website footer still shows two badges, Cash and Mobile Money. Add a bank badge when the account is live.

#### Modules checked

- All 56 installed modules compared with their code: every installed version matches. Nothing is waiting for an upgrade step.
- The Modules page's "An upgrade is available: Display Language and Currency Block" was false. That module's `config.xml` said 1.0.1 while its code and the database say 1.0.2 (an inconsistency in the upstream release). The file was corrected; no other module has the same mismatch.
- **About the 1-Click Upgrade module (`qloautoupgrade`), for the record:** it downloads the vendor's newest release from their servers and overwrites the platform's files in place, then runs that release's database changes. Here it must stay uninstalled: the site's files are rebuilt from this repository on every deploy, so the overwritten files would be replaced by the old ones while the database stayed upgraded, and our patches and edits would be lost in between. New releases are adopted in the repository (rule 18).

#### Verification (local, image rebuilt, PHP 8.3)

- `docker/smoke-test.sh`: 29 of 29.
- **JavaScript errors:** a browser run recorded every script error on the website (home, date picker, search results, add to cart, checkout, contact, sign in, phone menu) and the back office (dashboard, Modules, Bookings, Book Now, Reports, room type form, page editor): **none**. Pages report jQuery 1.12.4, Bootstrap 3.4.1, TinyMCE 4.9.11.
- **Things those libraries do, exercised:** date picker opens; a room was added to the cart from search results (guest count chosen, then Book Now) and appeared at checkout; dashboard charts drawn; a tooltip, a drop-down menu, the side menu arrows and a pop-up window all opened.
- **Text editor:** on the room type form it loads with the usual toolbar (screenshot reviewed). On the About Us page a marked line was inserted through the editor and saved; the back office reported success and the line appeared on the website page; it was then removed and the page saved again.
- **Bank transfer, as a guest in a real browser:** not offered while no account number is set. In settings, a script tag as the account number and "switched on with no number" were both refused; valid details saved. The guest's summary page showed the bank, branch, account name and number; confirming created booking `KKISXYMBF` as "Bank transfer", Awaiting payment, GH₵8,580.00, and the confirmation page repeated the details with the booking reference and the note. This was also a complete website booking on PHP 8.3 with the new libraries.
- **Not tested:** the editor's image and file manager buttons, and pasting from Word; the "Transplant" and other less-used back office dialogs; Safari and Firefox; recording the bank payment as staff; the live server.
- Local test data: a test bank account and Mobile Money number are saved in the local settings only.

### 2026-10-07 — MySQL 8.4 upgrade rehearsed on a copy (not applied)

- **What was done:** the local database and data volumes were copied into a separate throwaway stack (`sb84`), and that copy was started on `mysql:8.4` with the current app image (PHP 8.3). The normal local stack and its 8.0 data were not touched. **Nothing was changed in the code for this; `MYSQL_VERSION` still defaults to 8.0.**
- **Checked before starting, on 8.0:** every database account uses `caching_sha2_password` (8.4 switches the older `mysql_native_password` off by default, which is the usual cause of a failed upgrade); all 300 tables are InnoDB; no column uses the removed `FLOAT(M,D)` form; the server is started with no option that 8.4 removed.
- **Result:**
  - MySQL upgraded the data in place on first start, in about 35 seconds: data dictionary `80023` to `80300`, server `8.0.46` to `8.4.11`, both logged as completed, then "ready for connections". No errors or warnings in the database log.
  - `CHECK TABLE` on all 300 tables: 300 OK. Table count and the row counts of orders, customers and configuration match the 8.0 copy.
  - The app started against it without changes; `docker/smoke-test.sh` passed 29 of 29 on PHP 8.3.35 with MySQL 8.4.11.
  - A dump taken from the `backup` container, with the same client the nightly backup uses, completed and was the same size as on 8.0.
- **What this does and does not show:** the upgrade works on this data shape with this app. It was a small local database, not the live one, and the rehearsal did not include restoring an 8.0 backup into 8.4 or going back.
- **Still required before setting `MYSQL_VERSION=8.4` on the live site (unchanged):** a live backup restored successfully somewhere else; backups copied off the server; the same rehearsal on a staging copy of the live data; a quiet hour with the site in maintenance mode. It is one-way.
- **Left on the development machine:** five Docker volumes named `sb84_*` (about 260 MB) holding the upgraded copy. Remove them with `docker volume rm sb84_db_data sb84_app_data sb84_app_img sb84_app_upload sb84_app_download` when no longer wanted.

### 2026-10-07 — Server guides: COOLIFY.md, COOLIFY-STAGING.md, BACKUP.md; `staging` branch

- **Asked by the owner:** a detailed Coolify set-up guide, a staging guide, and a backup guide "in case the server crashes".
- **What:** three documents at the repository root. They are for whoever runs the server, not for back office users, so they are not in the in-app guides (rule 23 does not apply) and not on What's New.
  - `COOLIFY.md`: DNS, creating the resource (Build Pack Docker Compose, branch `salisberg-production`), the domain on the `app` service, every environment variable with what to put in it, the first deploy and its log lines, checks afterwards, first back office tasks, updating, changing the domain, what must never be done, and a fault table.
  - `COOLIFY-STAGING.md`: a second Coolify resource built from the new `staging` branch with its own passwords and volumes; keeping it private (maintenance mode, e-mail off); the `develop` → `staging` → `salisberg-production` flow; restoring a production backup into staging; rehearsing MySQL 8.4 there; deleting and recreating it.
  - `BACKUP.md`: what the nightly backup holds and what it does not (the `img`, `upload` and `download` volumes), taking a backup by hand, two ways of getting copies off the server (pulling with `scp`, or a host script with rclone that also archives the three picture volumes), proving a backup, restoring the live database, and rebuilding everything on a new server.
- **Why at the root and not under `docs/`:** `docs/` is a vendor folder. `.dockerignore` already leaves `*.md` out of the image, so none of them is served by the website. They hold no secrets or server addresses (the repository is public).
- **Branch `staging`** created from `develop` at the owner's request. Section 5's production steps are now given in full in `COOLIFY.md`; the restore procedure in the 2026-10-06 entry is repeated and extended in `BACKUP.md`.
- **Checked:** every variable, default, log line and file name in the guides was read from `.env.example`, `docker-compose.yml`, `docker/entrypoint.sh`, `docker/backup.sh` and `docker/smoke-test.sh`. The one-line command that rewrites `_DB_PASSWD_` in a restored settings file was run against a sample settings file (PHP 8.3) and the result passed `php -l`.
- **Not tested:** nothing in these guides was carried out on a Coolify server in this session. In particular: the Coolify screen and button names (written for v4; they vary by version), the staging restore from a production backup, the off-server script `salisberg-offsite.sh` (written, never run), and the full recovery on a new server. `BACKUP.md` says so beside the script. The first real run of each should be treated as the test, on staging.

### 2026-10-07 — Independent of QloApps; stylesheets tidied, `!important` halved

#### Direction

- **Owner, 2026-10-07:** "the goal of this project is not to depend on qloapps updates anymore." Recorded in section 1. Rules reworded to match: **11** (fix things where they are defined, instead of "do not edit core files"), **18** (upstream is a source of security fixes, not releases) and **28** (inherited code may be trimmed inside a file, with the same proof). Rule 29 (never move or rename) and rule 14 (licence headers) are unchanged.
- The owner also considered Tailwind and decided against it for this codebase: the markup comes from several hundred inherited templates written for Bootstrap 3, the scripts depend on Bootstrap's class names, and it would add a build step.

#### What was done

| Stylesheet | `!important` before | After |
|---|---|---|
| `themes/hotel-reservation-theme/css/salisberg.css` | 45 | 23 |
| `admin/themes/default/css/overrides.css` | 64 | 33 |

- **Step 1, flags that did nothing (41 removed):** each flag was removed on its own in a real browser and the computed style of every element its rule matches was compared. A flag was dropped only where nothing changed on any tested page, width and theme.
- **Step 2, flags that only existed to out-rank an inherited rule:** the inherited rule was changed instead (rule 11), and our flag, or our whole duplicate rule, removed.

  | Inherited file | Change |
  |---|---|
  | `themes/hotel-reservation-theme/css/daterangepicker.css` | Selected days, hovered days and "today" take the brand colours here. The four date picker rules in `salisberg.css` are deleted. One `!important` stays in this file because the date picker library's own stylesheet uses one. |
  | `modules/wkhotelfilterblock/views/css/wkhotelfilterblock.css` | Price slider bar and handle in gold. Our two slider rules are deleted. |
  | `themes/hotel-reservation-theme/css/modules/blockcart/blockcart.css` | Cart pop-up heading colour. |
  | `themes/hotel-reservation-theme/css/order-opc.css` | "Extra services" links in gold instead of pure blue (three places); `!important` removed from the room name colour. |
  | `themes/hotel-reservation-theme/css/global.css` | Heading underline and the extra-services link in gold. |
  | `themes/hotel-reservation-theme/css/product.css` | Extra-services price in gold. |
  | `admin/themes/default/css/admin-theme.css` | `!important` removed from the float of Quick Access and the account menu, and from the background of an empty list. |

- Inherited website stylesheets now use the `--sb-` colour tokens defined in `salisberg.css`. That file is loaded on every website page, so the tokens are always there; it also means those rules follow dark mode by themselves.
- `salisberg.css` link bumped to `?v=12`.

#### What is left, and why

- **Website (23):** the flattened two-layer buttons, which out-rank about a dozen inherited button rules spread over `global.css` and module stylesheets (6); hover and focus states of those buttons and of the menus (7); colours set by inline `style` attributes, which only `!important` can beat: booking status labels and the amenities block height (3); the reduced-motion rule, where `!important` is the point (1); rules for things not on screen in the test data, left alone: testimonials and the file upload button (3); the phone menu's close button (1); an accent rule that also has to beat our own broad dark-mode text rules (1); and a dark-mode tab colour that fights an inherited `!important` (1).
- **Back office (33):** top bar and side menu rules (about 20), which sit against a dense set of inherited `#header_infos` and `#nav-sidebar` rules; tooltip arrows and the calendar's selected day, which are only in the page while open (7); the rest are one-offs.
- **Next step if wanted:** the website buttons. Rewriting the inherited button rules in `global.css` so there is one layer, not an anchor and a span each with its own gradient, would remove a dozen flags at once.

#### Method and verification (local, real browser)

- Three small tools written for this, kept outside the repository: one removes each flag in turn and reports whether anything changed; one lists which other rule takes over when a flag is removed; one records the computed look of every element on a set of pages (71 properties each, plus `::before` and `::after`), and of interactive elements with `:hover` and `:focus` forced on, so a "before" and an "after" run can be compared. Two "before" runs are taken, so anything that differs between them (a carousel, a calendar still drawing) is treated as noise.
- **Website:** 14 pages (home, room, search results, contact, sign in, About Us, checkout with a room in the cart, password, 404, Our Properties, and as a signed-in guest: account, bookings, personal information, addresses), at 1440 and 390 wide, light and dark: 72 runs. After the changes: no differences beyond the noise.
- **Back office:** step 1 on 23 pages at three widths, light, dark and with the menu's old collapsed state forced: 165 runs, no differences. Step 2 on 17 pages at two widths, light, dark and collapsed: 89 runs, no differences beyond the noise on 15 of the pages. The dashboard and the room type form could not be compared element by element in step 2, because their element lists differ from one load to the next (charts, generated ids); the three step 2 back office changes concern the top bar and empty lists, which are the same on the other 15 pages.
- **Caught by the checks and corrected before finishing:** three flags that step 1 had judged unnecessary were needed only on hover or focus (desktop menu links, the Book Now button border, links in the top bar's drop-down menus) and were put back. In step 2, two flags turned out to be needed after all (the account menu's position in the top bar, and an accent colour that our own dark-mode rules would otherwise override) and were put back, with the inherited rule for the first restored too; and two dark-mode differences were fixed by adjusting our own dark rules.
- **Commit note:** the owner committed twice part-way (`ecb26e63`, `aca2148e`). The first predates the flags that were put back, so neither should be deployed without the changes that follow them (back office step 2, the `?v=12` bump and these records).
- **Not tested:** pages outside the sets above (a flag proven unnecessary on 23 back office pages could still matter on one of the other 56); right-to-left; Safari and Firefox; the testimonials block and the 1-Click pages, which are switched off; the live server. After deploying, returning visitors may for a few hours mix a cached old inherited stylesheet with the new `salisberg.css`, and see the stock blue on the date picker or slider until their browser refetches.
- No guide or What's New entry: nothing a guest, staff member or manager does or sees has changed.

### 2026-10-07 — Website buttons: inherited rules corrected, 15 more flags gone

Follow-up to the entry above. `salisberg.css` now has **8** `!important` flags (45 at the start of the day). Link bumped to `?v=13`.

- **Method:** all 15 remaining button, menu and tab flags were removed together in a trial, and the before/after record showed which elements changed. Only four kinds did, so only those inherited rules needed correcting; the general button rules in `global.css` did not, because our rules already out-rank them without help.

  | Inherited file | Change | Flags it made unnecessary |
  |---|---|---|
  | `css/modules/blockcart/blockcart.css` | Cart drop-down "check out" button: its own border, blue gradient and hover gradient removed, so it takes the shared button look. Cart pop-up button: its blue four-colour border removed. | The four on the flattened buttons and their hover |
  | `css/product.css` | Extra-service "Add" buttons take the brand green, and gold on hover, focus and press. | The two on `.btn-success` |
  | `css/product.css` | The room page tab link's colour is set with a selector that out-ranks the general tab rules, instead of `!important`. | The dark-mode tab colour |

- **In our own file:** the phone menu's hover background was declared for every width and then cancelled with `!important` on wide screens. It is now declared for narrow screens only. Seven other flags (account button, checkout buttons and their hover, newsletter button on checkout, menu close button, header link hover) changed nothing once the above was done.
- **One deliberate visible change:** an extra-service "Add" button reached with the keyboard used to turn the stock bright green (`#1cb62e`) with our dark green border; it now turns gold like every other button.
- **The 8 that remain, each for a reason:** the amenities block height and the booking status label colours (set by inline `style` attributes, 3); the reduced-motion rule (1); the file upload button, which fights an inherited `!important` and is not on screen now that the contact form takes no files (2); the testimonials photo size, a block that is switched off (1); one accent colour that must beat our own broad dark-mode text rules (1).

**Verification (local, real browser)**

- Same 72-run record as before (14 pages, 1440 and 390 wide, light and dark, with `:hover` and `:focus` forced on up to 45 kinds of interactive element per page). Before against after: the only difference is the service button's focus colour described above. Page-height differences on the phone homepage were shown to be noise by comparing two "after" runs with each other.
- The signed-in account button with its menu open, which the record does not cover (it needs a class added by a click): white name on green, in light and dark.
- **Mistakes in the tooling, found and corrected during this work:** one "before" record was taken a few seconds after the trial stylesheet had been copied in, so the first comparison showed no differences at all; that was noticed because it contradicted the earlier per-flag measurements, and the records were retaken. The comparison also used to stop at the first element whose class list differed between runs (a carousel's "active" item), skipping the rest of that page; it now ignores those classes.
- **Not tested:** the file upload button and the testimonials photo (not on screen); the order detail page of a past booking, which has its own button rules; right-to-left; Safari and Firefox; the live server.
- **Back office not touched in this step:** `overrides.css` still has 33.

### 2026-10-07 — Back office: inherited rules corrected, `overrides.css` down to 4 flags

Same method as the website buttons entry above: remove the flags in a trial, read from the before/after record what actually changes, correct the inherited rule responsible. `overrides.css` had 64 `!important` flags at the start of the day, 33 before this step, and has **4** now.

#### Changes in `admin/themes/default/css/admin-theme.css` (inherited, minified)

| What | Before | Now |
|---|---|---|
| Typeface | `"Open Sans"` (11 places) and `"Ubuntu Condensed"` (6 places) written into individual rules | `var(--sb-font)` in all 17 |
| Top bar text, hover, open menu, notification icons, "working" spinner | White and grey for a dark bar | The ink, link, canvas and muted tokens |
| Logo link | `padding-left: 140px !important` | Removed |
| Side menu search box | Dark strip with a white field | Transparent, as our rules already drew it |
| Panel headings and `h3` | `text-transform: uppercase` | Removed |
| Hint labels beside field names and column titles | Blue text on a pale blue chip | Removed, so ours apply |
| Tooltip arrows (8 rules) | `#555` | `var(--sb-tip)`; our four arrow rules are deleted |
| Selected day on the dashboard date switcher | Stock blue | `var(--sb-accent)` |

The edits were made by a small script that names each rule by its selector and one declaration and refuses to run unless it matches exactly once (the file is one minified line per block, so hand edits are easy to get wrong). The inherited stylesheet now depends on the tokens defined in `overrides.css`, which is loaded on every back office page including sign-in.

#### Changes in `overrides.css`

- 29 flags removed. Four arrow rules deleted.
- The drop-down menus in the top bar keep dark text on hover through an explicit rule (`.dropdown-menu a:hover`) instead of one `!important` out-ranking another.
- A dark-mode rule of ours (`span:not(.label):not(.badge) { color: inherit }`) was overriding the hint labels' colour once their flag was gone; it now leaves them out, using `:where()` so its weight is unchanged.

#### Deliberate visible changes

- Text typed into fields is in Inter, like the rest of the screen. It was Open Sans, because the inherited input rule out-ranked ours.
- `h3` headings that are not the direct child of a panel are no longer in capitals. Panel headings already were not.
- A top bar item reached with the keyboard now gets the hover highlight. The inherited rules style hover and focus together; before, our flags suppressed the focus half.

#### The 4 that remain

- Three on the selected day of the inherited calendar pop-up (`.datepicker td.active`), which is only in the page while open and was not in any record. Left as they were.
- One for a side menu that was collapsed to icons before that mode was removed (`body.page-sidebar-closed`): it fights two inherited `!important` rules for a state nobody can enter any more. Candidate for removal together with the inherited collapsed-menu rules.

#### Verification (local, real browser)

- 17 back office pages (sign-in, dashboard, bookings list and one booking, room types and the room type form, modules and the Salisberg Pay settings, e-mail, stats, Staff Guide, employees and the new employee form, customers and one customer, Book Now, Reports, logs) at 1440 and 700 wide, light, dark and with the old collapsed state forced: 89 runs, with `:hover` and `:focus` forced on up to 30 kinds of interactive element per page.
- Before against after, once the three deliberate changes and their knock-on widths are set aside: no differences. The sign-in button's colour differs by a few shades between any two runs, including two "before" runs, because it is caught part-way through its own fade.
- Tooltip arrows, which are only in the page while a tooltip is open: a real tooltip was opened on the new employee form; arrow and bubble are the same colour in light and in dark.
- **Found and fixed on the way:** the dashboard's selected-day button turned stock blue (fixed at the source, table above); hint labels turned grey in dark mode (our own dark rule, above); drop-down items turned green on hover (explicit rule, above).
- **Not tested:** the other back office pages (about 60); the calendar pop-up; pop-up windows; right-to-left, where `admin-theme_rtl.css` was not edited and still has the old fonts and colours; Safari and Firefox; the live server.
- **What's New:** one line for everyone under 7 October. No guide change: nothing has moved or been renamed.

### 2026-10-12 — Sidebar section renamed "Manage Hotel"

**What**

- The back office sidebar section **Hotel Reservation System** is now **Manage Hotel**. Its own existing sub-page, previously also called **Manage Hotel** (hotel address, phone, photos, policies — class `AdminAddHotel`), is renamed to **Hotel Details** so the two no longer share a name.
- `modules/salisbergguide/salisbergguide.php`: two entries added to the existing `$menuLabels` rename mechanism (rule 25/26, the same one used for the 2026-10-06 Orders → Bookings rename): `AdminHotelReservationSystemManagement` and `AdminAddHotel`. `MODULES_VERSION=12` so `renameMenuEntries()` runs again on the next deploy. As with every entry in `$menuLabels`, an entry is renamed only while it still carries the name it is expected to have, so a name changed by hand in the back office afterwards is kept.
- Every on-screen path in the guides and in `COOLIFY.md` that read "Hotel Reservation System" or "… › Manage Hotel" (the sub-page) was updated to match: `admin_guide.tpl` (9 places plus the "Open Manage Hotel" quick link and the "Attach rules to the hotel in Manage Hotel" line), `staff_guide.tpl` (4 places), `COOLIFY.md` (1 place).
- The one vendor template that names the sub-page in on-screen text, `modules/hotelreservationsystem/views/templates/admin/hotel_rooms_booking/helpers/view/view.tpl` (the "No active hotels" notice on Book Now), was corrected at the source (rule 11) rather than worked around.
- Guide (rule 23): no section moved, so no restructuring; path text throughout both guides now reads "Manage Hotel" / "Hotel Details" as above.
- What's New (rule 30): new "12 October 2026" entry. The section rename is in the everyone list (both Hotel Staff and Hotel Manager see this menu); the sub-page rename is under "For the hotel manager" only, since Hotel Staff is not granted `AdminAddHotel`.
- `CHANGELOG.md`: one line under a new 2026-10-12 "Changed" heading.

**Why asked this way**

- The owner asked only to rename "Hotel Reservation System"; the collision with the existing "Manage Hotel" sub-page was found while locating the tab's class name and flagged before acting. The owner chose to rename the sub-page too rather than leave the two names stacked.

**Verification (local, running stack)**

- `docker/setup-modules.php` run by hand (`docker exec -u www-data … php /usr/local/share/salisberg/setup-modules.php`) after copying the changed files into the running container: `qlo_tab_lang` shows `AdminHotelReservationSystemManagement` = "Manage Hotel" and `AdminAddHotel` = "Hotel Details".
- Logged into the back office by script (curl, following the real login POST and token) and fetched the dashboard, Admin Guide, Staff Guide and What's New pages: the dashboard's sidebar shows "Manage Hotel" and "Hotel Details" and no longer "Hotel Reservation System"; all three guide pages render with the new names and no PHP, Smarty or SQL errors (the one "Smarty" text hit on the Admin Guide page is the unrelated Advanced Parameters paragraph about template caching). The one remaining "Hotel Reservation System" string anywhere fetched is the historical sentence in the new What's New entry itself, which names the old label on purpose.
- `docker/smoke-test.sh`: 29 of 29, no PHP errors or warnings logged.
- **Not tested:** a real deploy (this was applied to the already-running local container and database, not through an image rebuild); the vendor "No active hotels" notice itself (it only shows when no hotel is active, which is not the current local state); every other place `AdminAddHotel`'s old name might have been assumed; the live server.

### 2026-10-12 — Website footer: Location and Contact column

**What**

- The website footer has a new "Location and Contact" column, next to Explore, Follow us on and Payment accepted: the hotel's address (icon-building), phone (icon-phone, `tel:` link) and email (icon-envelope, `mailto:` link).
- Source of the data: the same one already used by the Contact page's "Main Branch" card (`controllers/front/ContactController.php`, `$gblHtlAddress`/`$gblHtlPhone`/`$gblHtlEmail`) — `Shop::getAddress()` formatted with `AddressFormat::generateAddress()`, and `Configuration::get('PS_SHOP_PHONE' / 'PS_SHOP_EMAIL')`. Reusing it means there is one place this information comes from and the two pages can never disagree (rule 26).
- `modules/salisbergguide/salisbergguide.php`: new `hookFooter()` method and a `registerHook('footer')` call added to the existing `ensureSetup()` (so it reaches the already-installed module on the next deploy, the same way the Bookings/Manage Hotel menu renames do). New template `modules/salisbergguide/views/templates/hook/footer_location.tpl`.
- `themes/hotel-reservation-theme/css/salisberg.css`: new `.sb-footer-contact` rules in the existing Footer section; stylesheet link bumped to `?v=14` (`header.tpl`).

**Why this module, and why the `footer` hook directly**

- Looked for an existing home first (rule 25): no Salisberg module owns front-end content blocks. `salisbergguide` already hooks the front end for small additions (`addInterfaceAssets()`, "Add new ones here rather than in a new module") and already has the idempotent `ensureSetup()` machinery needed to register a new hook on an already-installed module — a whole new module for one footer column would be the kind of premature abstraction the project avoids.
- Rule 11 reserves direct edits to inherited files for *fixing* what is already there; this is new behaviour, so it was kept out of the vendor `hotelreservationsystem` module even though that module owns the address/phone data and already implements the near-identical `hookDisplayNav()` (header contact) and `hookDisplayAfterDefautlFooterHook()` (copyright).
- `blocknavigationmenu`'s Explore column hooks the `footer` hook (canonical name `displayFooter`) directly and renders its own `col-sm-3`; the new column does the same, matching an existing pattern rather than introducing the other one present in the stock footer (a "container" module wrapping a more specific sub-hook, used by the payment and language/currency columns).

**Found along the way, not fixed (out of scope for this change)**

- `PS_SHOP_ADDR1` already contains the installer's demo text with the shop name baked in ("Salisberg Hotels, Monticello Dr, Montgomery, 10010"), so `Shop::getAddress()` (which also prepends `PS_SHOP_NAME` as the address `company`) produces "Salisberg Hotels, Salisberg Hotels, Monticello Dr, Montgomery, 10010, United States". This already shows on the Contact page today; the new footer column inherits the same text rather than introducing it. Fixing it means tidying `PS_SHOP_ADDR1`/city/postcode/country into their own fields.
- The only back office page that writes `PS_SHOP_ADDR1`/`PS_SHOP_PHONE`/`PS_SHOP_EMAIL` is `AdminStoresController`, and it currently has no menu tab (`qlo_tab` has no row for class `AdminStores`), so there is no back-office way to change this address/phone/email short of editing the database directly. Neither of these was touched; the owner should decide whether to give `AdminStores` a menu entry or move this one piece of contact data into the hotel's own "Hotel Details" (`AdminAddHotel`) fields instead.

**Verification (local, running stack)**

- Copied the three changed/new files into the running container, re-ran `setup-modules.php`: `qlo_hook_module` shows `salisbergguide` now registered on `displayFooter` alongside `blocknavigationmenu`, `hotelreservationsystem` and `wkfooternotificationblock`, in addition to its existing `Header`/`actionAdminControllerSetMedia`/`actionAdminLoginControllerSetMedia` hooks.
- Fetched the homepage and the Contact page: both render 200 with no PHP/Smarty errors; the new column appears once on the homepage with the address, phone (`tel:0987654321`) and email (`mailto:booking@salisberg.com`) links, matching the Contact page's own text exactly (including the pre-existing duplicate-name quirk above).
- `docker/smoke-test.sh`: 29 of 29, no PHP errors or warnings logged.
- **Not tested:** a real deploy; the column with social links present (so "Follow us on" also shows, changing how many columns wrap per row); phone widths; dark mode is automatic here (the footer has always been dark) but was not screenshotted; the live server.

### 2026-10-12 — The hotel's real room types

**What**

- Six room types from the owner's first room list, each with its own price, bed and amenities: Standard Room 1 (GH₵200, double bed, Fan, TV), Standard Room 2 (GH₵250, double bed, + Breakfast, Wi-Fi), Standard Room 3 (GH₵250, twin beds, + Breakfast, Wi-Fi), Deluxe Room 1 (GH₵300, double bed, + A/C), Deluxe Room 2 (GH₵350, queen bed, + A/C, water heater), Executive Room 1 (GH₵450, king bed, + A/C, water heater, Snacks).
- The three demo room types that matched a tier name (General Rooms, Delux Rooms, Executive Rooms) were renamed and repriced into Standard Room 1, Deluxe Room 1 and Executive Room 1 in place, at the owner's choice, rather than left as stale extra listings next to the real ones.
- New script `docker/setup-room-types.php` (same shape as `setup-modules.php`): renames/reprices the three reused products, then creates the three new ones by duplicating the matching template room type through the same sequence the back office's own "Duplicate" button uses (`Product::add()` plus `Product::duplicateSuppliers/Attributes/Accessories/Features/...`, then `HotelRoomType::duplicateRoomType()`, which also assigns the location categories via `updateCategories()`). No individual rooms (room numbers/floors) are duplicated — the owner adds those once real numbers are known. Bed types (`htl_room_type_bed_type`) are set directly: Full Bed for "Double bed", Queen Bed, King Bed, and two Twin Bed rows for "Twin room (2 single beds)". Amenities have no structured per-room-type field in this platform (the "Features" tab is per-hotel, not per-room-type; CSV import's "Feature IDs" is the unrelated generic PrestaShop Features system), so they are written as a bullet list into the description and short description.
- Wired into `docker/entrypoint.sh` as a new versioned step, `ROOM_TYPES_VERSION` (currently 1), run right after the modules step and gated the same way (`/data/.room-types-version` marker). Each room is skipped individually once it already carries its new name, so an edit made afterwards in the back office is kept (rule 22).

**Verification (local)**

- Ran the script by hand against the running container, then again to confirm every line reports "already in place/exists, skipped". `qlo_product`, `qlo_htl_room_type`, `qlo_htl_room_type_bed_type` and `qlo_category_product` all checked by query and matched the intended name/price/bed/category for all six products.
- All six room pages (ids 1, 2, 3, 11, 12, 13) return 200 with no PHP errors; Standard Room 3's page shows "Twin Bed" twice and the price 250; the room type editor opens cleanly for the newly created Standard Room 3.
- Rebuilt the image from scratch and recreated the container (not just hot-patched): the entrypoint log shows `Room types step v1 done` and, on a second recreation, all six lines report "skipped" — the deploy step works on a container that has never run it before, which is what a real redeploy looks like. `docker/smoke-test.sh`: 29 of 29 both times.
- **Not tested:** the live server; a hotel with more than one property (the script assumes a single id_hotel, matching this site); re-running after an admin has manually edited one of the six rooms (the per-room skip-if-renamed check was exercised only for the original demo names, not for a hand-edited name).

### 2026-10-12 — CSV Import also accepts Excel (.xlsx); its sample downloads were broken; simplified for non-technical users

**What**

- **Excel upload.** `override/controllers/admin/AdminImportController.php` overrides only the upload step (`ajaxProcessuploadCsv()`): if the uploaded file ends in `.xlsx`, it is parsed with a newly vendored library and rewritten, in place, as a semicolon-separated CSV before the stock method runs. Every other line of the 3,000-line vendor import engine — column mapping, validation, the import itself — is untouched; from the stock code's point of view a normal `.csv` was uploaded. Old binary `.xls` is not read.
- **Library:** `tools/simplexlsx/SimpleXLSX.php` (Shuchkin/SimpleXLSX, MIT, latest release 1.1.19, security fixes included), vendored next to `tools/pear` and `tools/random_compat` — the existing home for small bundled PHP libraries (rule 25). Its own licence file is kept alongside it (rule 14/18).
- **Sample downloads fixed.** The back office's "Download sample csv files" panel (Advanced Parameters › CSV Import) links to `docs/csv_import/*.csv`, but `.dockerignore` excluded the entire `docs` folder, so on a real deploy every one of those links returned 404 — a pre-existing bug, found while adding the `.xlsx` versions, not something this change introduced. Fixed by excluding only the vendor readmes and licence copies under `docs/`, keeping `docs/csv_import/` and `docs/index.php`.
- **Simplified `.xlsx` samples.** For the entities a non-technical hotel staffer would actually fill in by hand — Hotels, Room Types, Service Products, Customers — the `.xlsx` download has only the columns a person would realistically type: name, price, bed/occupancy numbers, contact details, description. Columns the app can work out itself (web address slug, meta title/keywords/description, tax rule, advance-payment settings, length-of-stay limits, image URLs) are left out of the `.xlsx` version; `AdminImportController` already fills most of these from sensible defaults or from the name (`Tools::link_rewrite()` auto-generates the URL slug when it is absent — confirmed by reading the import code, not assumed). The full-column `.csv` samples are untouched, for anyone who wants to set every field. Rooms and Categories were already short enough (4-6 columns) to leave as they were; Bookings was left full, since bulk-importing bookings is an administrator task, not a front-desk one.
- An extra Room Types example, `room_types_import_example_simple.xlsx`, ships pre-filled with the hotel's own six room types (same simplified columns), so a manager can open it, add their own rows, and import. The simplified Room Types sample is `room_types_import_simple.xlsx` (not `room_types_import.xlsx`, matching every other entity's `_simple` naming) — see the note below on why.
- Admin Guide, section 16 (CSV Import) and What's New updated; both gated `{if $sb_is_admin}` since CSV Import has no SuperAdmin-only menu grant to any other profile.

**Why overriding the upload step rather than rewriting the importer**

- Rule 11: new behaviour belongs in `override/` or a module, not a rewrite of vendor logic. Converting at the point of upload, before the stock code ever sees the file, means the 3,000-line import engine — validation, every entity's field list, the whole chunked-AJAX processing flow — needed no changes and keeps working exactly as before for ordinary `.csv` uploads.
- `move_uploaded_file()` only succeeds on the exact temp path PHP itself registered as an upload; the converted content is written into that same path with `file_put_contents()` rather than a new file, so the stock code's own `move_uploaded_file()` call a few lines later still succeeds unmodified.

**Verification (local)**

- Generated real, valid `.xlsx` files with a small script (`ZipArchive`, inline-string cells — no external tool was available to "Save As" from) and round-tripped every one of them through `SimpleXLSX::parse()` directly: all seven base samples and the filled example parsed back with the expected row and column counts.
- Uploaded a real `.xlsx` through the actual AJAX endpoint (`action=uploadCsv`) on a running container, twice: once against a hot-patched container (worked, but only after finding and deleting a stale `cache/class_index.php` left over from before the override existed — see below), and once against a container freshly rebuilt and recreated from the image (worked immediately, no cache issue). The converted file on disk in `admin-salisberg/import/` was byte-for-byte the same shape as a native CSV upload, including correct quoting.
- Confirmed the pre-existing 404 bug and its fix: before the `.dockerignore` change, `docs/csv_import/room_types_import.csv` (already linked from the stock Import page, never touched by Salisberg) returned 404 against a freshly rebuilt image; after, it and all eight `.xlsx` links return 200, and the vendor `docs/readme_en.txt` still does not serve (403).
- `docker/smoke-test.sh`: 29 of 29 on the final rebuilt-and-recreated container.
- **Caught during this work:** `cache/class_index.php` is PrestaShop's own cache mapping class names to files, including override resolution; it is not committed (generated at runtime) and is not on a volume, so it does not exist on a fresh container and is rebuilt correctly from scratch on first boot. It only went stale here because this session kept hot-patching files into a single long-running dev container across many edits instead of rebuilding each time; deleting it forced a correct rebuild. This is a note for whoever tests admin controller overrides locally the same way, not a deploy-time fix.
- **Resolved the same day:** `docs/csv_import/room_types_import.xlsx` and `room_types_import_example.xlsx` (the un-simplified, pre-correction versions) could not be overwritten or deleted on the local filesystem at first — Windows reported them locked by another process, and `~$room_types_import.xlsx` / `~$room_types_import_example.xlsx` lock files in the same folder showed they were open in Microsoft Excel at the time (most likely opened to look at the downloads while this work was in progress). The simplified replacements were written under new names instead — `room_types_import_simple.xlsx` and `room_types_import_example_simple.xlsx` — and the back office's download links (`form.tpl`) point at those. Once Excel released the lock later the same session, the two old files were deleted and the matching `.dockerignore` exclusion removed; `room_types_import_simple.xlsx` / `room_types_import_example_simple.xlsx` are the only Room Types `.xlsx` downloads now.
- **Not tested:** the live server; a `.xlsx` with formulas, multiple sheets, or non-UTF-8 text; a file large enough to need the import's chunked processing to run more than one AJAX tick; opening the generated sample files in actual Excel or LibreOffice (only machine-verified via SimpleXLSX, since neither is available in this environment).

### 2026-10-12 — Friendly URLs switched on

**What**

- Website links no longer show `index.php` or a `.php` extension: a room page reads `salisberg.com/salisberg-hotels/1-standard-room-1.html` instead of `salisberg.com/index.php?controller=product&id_product=1`. Old-style links still resolve (PrestaShop keeps both working side by side). The back office is unaffected — admin links always use `index.php` by design, Friendly URL only changes the storefront.
- `docker/setup-modules.php` sets `PS_REWRITING_SETTINGS=1` once, gated by `MODULES_VERSION` (bumped to 13) the same as the rest of that script. The `.htaccess` file itself is written by a separate new script, `docker/generate-htaccess.php`, run **unconditionally on every start** (see the correction below for why it could not share the gated step).
- No custom URL patterns were added; PrestaShop's `Dispatcher` has built-in default route patterns for product/category/CMS pages used automatically when no `PS_ROUTE_*` override exists in the database (confirmed none exist here — the installer never wrote any), so this was purely a one-setting change plus the generated `.htaccess`, not a routing design task.
- Admin Guide section 9 (Website content) has a new row, admin-only, saying Friendly URL is kept on by the deploy and that turning it off is undone on the next deploy. What's New updated.

**Why this was safe to turn on without asking**

- `AllowOverride All` and `mod_rewrite` (`a2enmod rewrite`) were already in place from the original Docker setup (section 4), so nothing in the serving stack needed to change, only a PrestaShop setting plus the file it generates.

**Verification (local, running stack, then rebuilt and recreated)**

- Enabled by hand first: homepage links changed from `index.php?controller=...` to slug-based URLs; both `salisberg.com/salisberg-hotels/1-standard-room-1.html` and `salisberg.com/contact-us` returned 200 with the correct page title; the old `index.php?controller=product&id_product=1` form still returned 200 too. One link, "Our Properties" (a theme-added custom page, not a catalog/CMS entity with its own `url_rewrite` record), still shows `index.php?controller=our-properties` — expected: Friendly URL rewrites entities with a `meta_lang.url_rewrite` row (products, categories, CMS pages and the few controllers PrestaShop registers one for), not every custom front controller; this is normal PrestaShop behaviour, not a bug introduced here.
- The back office's own SEO & URLs page opened without error after the change.
- Rebuilt the image and recreated the container from scratch: entrypoint log showed `Friendly URLs enabled (.htaccess up to date)` and `Modules step v13 done` on first boot; the same friendly link returned 200 immediately. `docker/smoke-test.sh`: 29 of 29 — see the correction below, this result was real but incomplete.
- **Not tested (at the time):** recreating the container a second time without rebuilding, which is exactly what exposed the bug below.

**Correction, same day: `.htaccess` went missing on the next redeploy — seven storefront pages 404'd**

- Having proven the feature on that first rebuilt-and-recreated container, two more rounds of unrelated edits (the Excel samples, then the `.dockerignore` fix) each ended with a rebuild-and-recreate cycle, as section 15's own rules require. On the fourth recreation, `docker/smoke-test.sh` suddenly failed 7 of 29: search results, contact, sign in, password reset, cart/checkout, our properties and about us all returned 404. `.htaccess` was missing from the container's filesystem entirely.
- **Cause:** `.htaccess` was being written inside `setup-modules.php`, gated by `MODULES_VERSION` the same as the database changes earlier in that script. But `.htaccess` lives in the code filesystem, not a persisted volume (section 3's table), so it does **not** survive a redeploy — a brand new container starts with no `.htaccess` at all. The version marker that gates `setup-modules.php` lives in `/data` (persisted) and already said "13" from the previous container's successful run, so the whole script, `.htaccess` included, was skipped on the next container even though the file did not exist on its filesystem. Every redeploy after the first would have shipped with Friendly URL switched on in the database but no `.htaccess` to make it work — a worse state than never enabling it, since every storefront link including the ones that don't need rewriting (contact, sign-in, checkout) redirects to a URL that 404s, rather than just staying on the old `index.php?...` form.
- **Fix:** split the two concerns. `docker/setup-modules.php` only sets the one-time database flag now. A new script, `docker/generate-htaccess.php`, regenerates `.htaccess` from that flag on **every** start, unconditionally, the same "every run" treatment already used for the shop domain (`SYNC_DOMAIN`) — correct, because both are facts about the filesystem/environment that must be re-established on every fresh container, not "has this version's change been applied to the data" facts.
- **Lesson:** a deploy step that writes to the filesystem (not the database) cannot share a run-once-per-version gate with database steps, even when they are triggered by the same feature and even when the first test — a single rebuild-and-recreate — looked completely clean. The project's `AGENTS.md` rule 7 ("the entrypoint must stay idempotent... safe to repeat") was followed for each step individually; the bug was in assuming "ran once, version marker set" was equivalent to "the file is now on disk," which is only true for state that lives in a persisted volume.
- **Verification:** rebuilt and recreated once (clean, 29 of 29, `.htaccess` present), then force-recreated the *same* image a second time with no rebuild in between — the exact sequence that triggered the bug. `.htaccess` was present both times; the entrypoint log on the second recreation showed only `[generate-htaccess] .htaccess written (Friendly URLs)`, with no `Modules step`/`Room types step` lines, confirming the database-gated steps correctly stayed skipped while the filesystem step correctly ran anyway. `docker/smoke-test.sh`: 29 of 29 on both recreations.
- **Not tested:** the live server; CMS pages other than Contact Us; search-engine indexing of the new URLs (no canonical/redirect strategy was reviewed for already-indexed `index.php?...` links, since the site has not been live/indexed yet).

### 2026-10-12 — Live incident: a booking vanished; traced to a room type with no rooms; Admin Guide now warns about it

**What happened**

- The owner reported, on the live site: a guest completed checkout and reached a confirmation page, but the booking is nowhere in Bookings, and every room shows as available regardless. The room type involved was "Standard Room" (GH₵500), created directly in the live back office by a Hotel Staff employee who had been given elevated Products permissions for the purpose (confirmed intentional, not a permissions bug — `AdminProducts` has been view-only for `$staffAccess` since the Hotel Staff profile was first introduced; a grant made by hand in Permissions is never reset by a deploy, by design, so it is expected to persist).
- Production was found to be three commits behind `develop` at the time (none of this session's room-type, Friendly URL or CSV/Excel import work had reached it), so the live "Standard Room" is unrelated to the six room types added earlier in this file's 2026-10-12 entries; it is a separate, real room type the owner's team created on the live site.
- Checked via `WebFetch` against `https://salisberg.com` (read-only; no production data was changed): the homepage listed Delux Rooms, Executive Rooms, Standard Room and Luxury Rooms, with Standard Room's price (GH₵500) matching nothing in this repository's history — confirming it genuinely was created independently on the live site, not by any commit here.

**Root cause, traced through the code (not the live database, which was not accessible)**

- `modules/hotelreservationsystem/classes/HotelRoomInformation.php`'s `getRoomTypeAvailableRoomsForDateRange()` computes availability as "all rows in `htl_room_information` for this room type, minus the ones with a conflicting row in `htl_booking_detail`." A room type with zero rows in `htl_room_information` — because nobody used its "Rooms" tab to add individual room numbers after creating it — returns an empty set from that query every time, for every date range, which is indistinguishable at that layer from "every room is booked." The storefront listing itself does not appear to gate on this count before showing a room type as bookable; it was reproduced locally (see the 2026-10-12 room-types entries above) that a product with no `htl_room_information` rows still renders a working product page.
- `HotelCartBookingData::updateCartBooking()` does guard the final add-to-cart step on `$totalAvailableRooms >= $roomsRequired`, which should refuse a room type with zero rooms. The owner's account confirms the guest nonetheless reached a confirmation page, so either that guard did not fire on the specific code path exercised, or the failure is further down the booking/order-validation flow; this was not pinned down exactly, because there is no shell or database access to the production server from this environment, and the exact live request could not be replayed.
- This was **not fixed in code** this session: the mechanism is understood well enough to document and warn about, not well enough to safely patch blind, and production is still three commits behind `develop` with no deploy requested.

**Fix applied: guide warning, not a deploy**

- `modules/salisbergguide/views/templates/admin/admin_guide.tpl`, section 3 ("Room types and rooms"): a new `sb-warn` paragraph, right after the existing "Save" step, states plainly that a room type with nothing in its Rooms tab can still be found, added to cart and taken to a confirmation page by a guest, but the booking will not be recorded, and to always add at least one real room number before switching a new room type on.
- `CHANGELOG.md` and the What's New page (manager section, 12 October 2026) updated to match.
- No change was made to `$staffAccess`, Permissions, or any booking/availability code. The owner was advised that Hotel Staff being able to add room types, when given the right by hand in Permissions, is working as intended, not a bug to close.

**Why this stayed as a guide fix and not a code patch**

- Fixing the underlying gap properly — refusing to show a room type as bookable at all until it has at least one room, or blocking checkout earlier with a clear message — is real behaviour change to vendor booking code (`hotelreservationsystem`), which under rule 11 belongs in `override/` or a module, done deliberately and tested end to end, not guessed at without being able to see the actual failing request on the live server. It is a good candidate for a follow-up change once there is a way to reproduce it (for example, creating a fresh empty room type on the local stack and deliberately walking it through checkout) rather than reasoning from code alone.

**Verification**

- Confirmed via `git log -p` that `AdminProducts` has only ever been `array(1, 0, 0, 0)` (view-only) in `$staffAccess`, in every commit since the Hotel Staff profile was introduced — ruling out a regression in this repository's code as the cause of staff being able to add a room type (the access was granted by hand in Permissions, which is expected to be possible and to persist).
- The two guide/changelog files were copied into the running local container and reloaded as a signed-in SuperAdmin: the Admin Guide's section 3 shows the new warning and the What's New page shows the new manager-facing line, both with no PHP or Smarty errors.
- **Not tested:** reproducing the exact failure (an empty room type reaching a confirmation page) on the local stack; the live server, which was only read via `WebFetch`, never modified; whether the guard in `updateCartBooking()` is actually what's being bypassed, versus a different step in checkout.
- **Still needed from the owner:** add at least one room number under Standard Room's Rooms tab (Rooms & Services › Manage Room Types › Standard Room › Rooms); worth checking Delux Rooms, Executive Rooms and Luxury Rooms have real rooms too, since they were edited independently of this repository and their state on the live server is not known from here.

### 2026-10-12 — The real cause of "staff can't see their own bookings": a second, undocumented permission layer

**What the owner reported, in order**

1. A guest booking "doesn't appear" and all rooms show available (the entry above — traced to Standard Room having no rooms in its Rooms tab).
2. "We tried to book with admin and it works fine. We gave the staff rights and still cannot book. The staff clicks book now but the booking doesn't register. It still remains in the cart." All of this in the **back office** Book Now page, not the website.
3. "No showing on the dashboard. No revenue data. No check-ins and out showing."

Reports 2 and 3 turned out to share one cause, found by reproducing the exact flow locally rather than reasoning from code alone.

**Reproduced locally, step by step, as both roles**

- Created a throwaway Hotel Staff employee (`staff.test@example.com`) and used `curl` to drive the *exact* back office flow as both that account and the SuperAdmin account: `ajaxProcessAddDataToCart` (Book Now's "add room to cart"), then `submitAddOrder` (the "create order" submit on `AdminOrders&addorder`), reading the real JSON/HTML responses and the database after each step — not guessing from the source.
- **Add to cart:** succeeded for both roles identically (`{"success":true,...}`), and a row appeared in `htl_cart_booking_data` for both. Staff's default permissions (`$staffAccess`, unchanged since this profile was introduced) were sufficient for this step.
- **Create order:** also succeeded for both roles — an `qlo_orders` row and a matching `htl_booking_detail` row were created correctly for staff's booking (order reference, correct customer, correct total, state "Payment accepted", `valid=1`). The booking **was** being recorded.
- **What staff actually saw:** `submitAddOrder` redirects to `...&id_order=N&vieworder&conf=3` to show the new order. For staff, that redirect landed on a page titled just "Bookings" with the error "You do not have permission to view this." — even though `AdminOrders` view access is granted in `$staffAccess`. From the owner's or a staff member's point of view this looks exactly like "the booking didn't register" and "it still remains in the cart": nothing confirms success, and clicking back shows the same cart.

**Root cause: `qlo_htl_access`, a permission layer this project never touched**

- QloApps has a *second* access-control table, separate from PrestaShop's own tab-based Permissions grid (the one `$staffAccess`/`$managerAccess` configure): `htl_access` (`id_profile`, `id_hotel`, `access`), consumed by `getProfileAccessedHotels()` in `HotelBranchInformation.php` and checked directly in `AdminOrdersController`, `AdminDashboardController`, `AdminProductsController`, `AdminCartsController`, `AdminStatsController`, `dashinsights`, `statssales` and several other controllers — every one of them silently filtering out, or outright refusing, anything tied to a hotel the signed-in employee's profile isn't granted. It is managed from a "Hotel Permissions" panel that appears on the **same** Administration › Permissions page as the normal grid, once a profile is selected, easy to miss because it looks like a second, unrelated panel rather than part of the same screen.
- `SELECT * FROM qlo_htl_access` locally showed exactly the bug: `(SuperAdmin, hotel 1, access=1)`, `(Hotel Staff, hotel 1, access=0)`, `(Hotel Manager, hotel 1, access=0)`. These rows are created automatically by the vendor's own profile-creation code with `access=0` by default; nothing in this project's setup (`installProfile()`, `ensureSetup()`) ever touched them, because this layer was never discovered when the Hotel Staff and Hotel Manager roles were first built (2026-10-06). **Hotel Manager was affected too**, not just Hotel Staff — the owner had not yet tested booking visibility or the dashboard as a manager, so this had not surfaced for that role yet.
- This single gap explains report 2 (booking created, then refused on view) and report 3 in full: the dashboard's revenue, occupancy and check-in/out widgets all scope their SQL to `getProfileAccessedHotels()`'s result, which is empty for both roles, so they render with no data and no error — indistinguishable, from the screen, from "nothing has been booked."

**Fix**

- `modules/salisbergguide/salisbergguide.php`: new method `grantHotelAccess()`, called from `ensureSetup()` (so it runs on every deploy, like the rest of that method). For Hotel Staff and Hotel Manager, for every currently active hotel, it sets `access = 1` in `htl_access` if the row is missing or still 0; it only ever raises 0 to 1, never touches a row already at 1, and is not gated by a "ran once" flag the way `$staffAccess`/`$managerAccess` deliberately are — because, unlike a tab permission an admin might deliberately narrow, "this role cannot see the one hotel this project runs" is never a correct state to leave standing, and the method already only acts on rows that are still at the broken default.
- `MODULES_VERSION` bumped to 14 so this reaches the live database on the next deploy without any other action.

**Verification (local, running stack)**

- Reset `htl_access` to `0` for both roles by hand, deployed the fix, ran `setup-modules.php`: both rows flipped to `1`, logged `salisbergguide: granted hotel access to 2 profile/hotel row(s)...` (PrestaShop Logs, severity 1). Ran it again immediately after: no change, no log line — idempotent.
- Re-ran the exact `vieworder` request for the order staff had created earlier in this session, with no other change: before, "You do not have permission to view this."; after, the real "Bookings > View" page. Same isolated A/B result for loading the dashboard as staff (200, no errors; full data verification through the dashboard's own AJAX widgets was not done — see below).
- `docker/smoke-test.sh`: 29 of 29.
- **Not tested:** the dashboard's actual revenue/occupancy/check-in figures before and after (only that the page loads without error in both states) — the widgets fetch their numbers through further AJAX calls this session did not individually replay; `AdminCarts`, `AdminStatsController` and the other controllers in the "uses htl_access" list, beyond Orders and Dashboard; a second hotel (this fix reads "every active hotel" and should extend correctly, but there is only one hotel to test against); the live server.
- **Still needed on the live server:** this fix is on `develop`, not yet deployed (see the branch-state note elsewhere in this file). Until it is deployed, the owner can unblock staff and manager accounts immediately, with no deploy needed, from **Administration › Permissions**: select the Hotel Staff (then Hotel Manager) tab, find the **Hotel Permissions** panel, and tick the hotel there.

### 2026-10-12 — "Catalog" renamed "Rooms & Services"

- The sidebar section "Catalog" (Manage Room Types, Manage Service Products, Categories, Features, Bed Types) is now "Rooms & Services" — e-commerce terminology replaced with what the section actually is, matching the direction already applied to "Orders" → "Bookings" and "Hotel Reservation System" → "Manage Hotel".
- Added to the same `$menuLabels` mechanism (rule 25/26) in `modules/salisbergguide/salisbergguide.php`; `MODULES_VERSION` stayed at 14 (bundled with the `htl_access` fix above, both deployed by the same version bump). Admin Guide's two `Catalog ›` paths (section 3, section on paid extras) updated to match; What's New given a line.
- **Verification:** ran `setup-modules.php` locally, confirmed `qlo_tab_lang` for `AdminCatalog` reads "Rooms & Services"; Admin Guide page reloaded with the new path text and no errors; `docker/smoke-test.sh` 29 of 29.

### 2026-10-13 — Staff still could not finish a booking: Booking Carts needs Add and Edit

**Report**

- After the hotel was ticked for Hotel Staff in Hotel Permissions (previous entry), the owner reported that a Hotel Manager could book but Hotel Staff still could not: the rooms stayed in the cart.

**Why the previous entry missed it**

- The local reproduction on 2026-10-12 attached the guest to the test cart with an SQL `UPDATE` to save time, then submitted the order. That skipped the one step that fails. The order submit worked, so the flow was wrongly recorded as working for staff apart from the view step.

**Cause, reproduced without the shortcut**

- Book Now's cart button opens `AdminOrders&addorder&cart_id=N`. That form (`admin/themes/default/template/controllers/orders/form.tpl`) does all its cart work by AJAX against `AdminCarts`: choosing the guest, addresses, vouchers, occupancy, extra services. `AdminCartsController::ajaxPreProcess()`, which sets `id_customer` and `secure_key` on the cart, runs only when `tabAccess['edit'] === 1`; the service calls need `add`. With view only, every call returns a normal-looking JSON summary and changes nothing.
- `$staffAccess` and `$managerAccess` both had `'AdminCarts' => array(1, 0, 0, 0)`.
- Run as a Hotel Staff test account with those defaults: add to cart succeeded; after the form's AdminCarts call the cart still had `id_customer = 0`; `submitAddOrder` returned HTTP 500 (`Column 'secure_key' cannot be null`, from `validateOrder()` on a cart with no customer); no order row. With `edit` set on AdminCarts and nothing else changed: `id_customer = 2`, order created, the booking page opened.
- The owner's Hotel Manager works on the live site although its default is the same; that profile's rights on the live server were presumably widened by hand. It is not known from here.

**Fix**

- `modules/salisbergguide/salisbergguide.php`: `AdminCarts` is `array(1, 1, 1, 0)` in both access lists, for new installs. New `grantCartEditing()`, called from `ensureSetup()` after `grantHotelAccess()`, raises `add` and `edit` on `AdminCarts` for both profiles where either is still off (existing profiles are never reset by `installProfile()`, so the defaults alone would not reach the live site). It never lowers a right and logs each profile it changed. `MODULES_VERSION=15`.
- Guide (rule 23): Admin Guide section 1, "Change what a profile can do", has a warning listing the three things a profile needs to take bookings (Bookings, Booking Carts, Hotel Permissions). SuperAdmin only, as Permissions is. What's New and `CHANGELOG.md` have 13 October entries.

**Verification (local, running stack)**

- Reset `add` and `edit` to 0 for both profiles, copied the module in, ran `setup-modules.php`: both rows became `1,1,1,0` and two lines were logged. A second run changed nothing and logged nothing.
- The full flow as the staff test account (sign in, add to cart, open Add new, the form's AdminCarts call, submit): guest attached, order created, "Bookings > View" opened. Three runs in all: broken defaults (fails as reported), right granted by hand (works), right granted by the new method (works).
- Admin Guide and What's New pages load with the new text; `docker/smoke-test.sh`: 29 of 29.
- **Not tested:** the flow in a real browser (the calls were made with `curl`, in the order the form makes them); vouchers, extra services and occupancy changes on the form as staff; a new guest created from the form; the live server.
- Local test data: employee `staff.test@example.com` and bookings for December 2026 exist only in the local database.

### 2026-10-13 — Contact form: no Title, no Send To

- The website Contact form lost its Title field (`themes/hotel-reservation-theme/contact-form.tpl`) and its "Send To" choice. The owner asked for it to be simple, with messages going to users in the back office.
- "Send To" uses the platform's own switch, `PS_CUSTOMER_SERVICE_DISPLAY_CONTACT`, set to 0 by the content step (`CONTENT_VERSION=5`). The stock controller then uses `PS_CUSTOMER_SERVICE_CONTACT`.
- The controller still requires a title and a valid contact, so `override/controllers/front/ContactController.php` fills them in: the title is the first 60 characters of the message; if `PS_CUSTOMER_SERVICE_CONTACT` is not a real contact it is set to the first contact. Messages are stored as customer threads (Customers › Customer Service) and mailed to that contact's address, as before.
- Admin Guide section 9, CHANGELOG and What's New updated.
- **Verification (local, hot-patched into the running container):** with the switch at 0, the Contact page has no Title and no Send To field; posting a message created customer thread 2 (contact 2, the message start as its title) and showed the success notice, with no PHP errors logged. The `PS_CUSTOMER_SERVICE_CONTACT` setting was unset locally, so the first-contact fallback is what was exercised.
- **Not tested:** the content step through a rebuilt image (the switch was set by hand locally); the email to the contact address (no SMTP); the live server.

### 2026-10-13 — Import is Excel only

- Owner: remove the CSV imports and examples, keep Excel only. All `docs/csv_import/*.csv` samples are deleted and their links removed from the import form (`admin/themes/default/template/controllers/import/helpers/form/form.tpl`); the supply-order links went too (no such entity in this product, and no Excel version). The folder keeps its name because the page links to it by path (rule 29).
- `override/controllers/admin/AdminImportController.php` now refuses anything that is not `.xlsx` (the error shows in the upload box). The stock engine still reads CSV internally, so the `.xlsx` is converted to a temporary CSV exactly as before.
- **Bug found in the earlier Excel feature:** the form's browser-side filter was still `acceptFileTypes: /(csv)$/`, so a real browser would have refused an `.xlsx` before it reached the server. The earlier tests used `curl` and missed it. The filter is now `xlsx`.
- Admin Guide section 16, CHANGELOG and What's New updated.
- **Verification (local, hot-patched):** the import page loads with 0 `.csv` links and 8 `.xlsx` links and the `xlsx` filter; uploading a `.csv` returns "Please upload an Excel (.xlsx) file."; uploading `hotels_import.xlsx` is converted and accepted; a `.csv` sample URL returns 404.
- **Not tested:** the upload box in a real browser (the filter text was read from the page, not exercised); completing an import of a converted file; the live server.


### 2026-10-13 — Contact form: links refused and never clickable; Customer Service renamed Inbox; Webmaster kept

- **Owner:** links must not be sendable through the Contact form, and any that exist must not be clickable, so non-technical staff cannot click a malicious link. Webmaster stays as a contact (the earlier removal step was dropped before deploy); the Customer Service page is renamed Inbox.
- `override/controllers/front/ContactController.php`: markup is stripped from message, name, phone and subject, then anything that looks like an address (a scheme such as http:// or javascript:, `www.`, or a bare name ending in a common domain suffix) refuses the submission with a form error. Plain email addresses are allowed. Short suffixes that are ordinary words after a missing space (`.to`, `.us`, `.me`, `.co`, `.de`) are deliberately not in the list.
- `controllers/admin/AdminCustomerThreadsController.php` (inherited, fixed at the source per rule 11): the stock code wrapped URLs in `<a>` tags when showing a message; that is removed, so stored links show as text. The template escapes the text.
- `$menuLabels` in `modules/salisbergguide/salisbergguide.php`: `AdminCustomerThreads` Customer Service to Inbox (`MODULES_VERSION=16`). The page's own heading and some vendor sentences may still say Customer Service.
- Guides: Staff Guide menu table and messages section, Admin Guide section 9 rows; What's New and CHANGELOG updated.
- **Verification (local, hot-patched):** detector unit-tested on 11 samples; real form posts with `http://` and `www.` links were refused with the message; a plain message was not seen stored (likely the 6-per-hour rate limit, not confirmed). **Not tested:** the rename and the plain-message path after rebuild; email client behaviour for bare text in staff notification mails.

- **Inbox design (same day):** `controllers/admin/AdminCustomerThreadsController.php` list reduced to From, Email, Phone, Message (link to the conversation via `getMessageLink()`, escaped), Status, Handled by, Received; the IMAP options panel is dropped for non-SuperAdmins (`isSuperAdmin()` in the constructor); status labels renamed; `getCustomerLink()` now escapes the name. The Contact form's generated title ends on a whole word. Guides: Staff Guide section 9 explains the columns and statuses, and that links are never clickable; Admin Guide section 9 rows. **Verified locally:** the Inbox loads as SuperAdmin with the new columns and a working link; plain message accepted and stored, link refused (needs the hidden `url` and `contactKey` fields in a hand-made post, which is why earlier curl tests of the plain path failed). **Not tested:** the Inbox as Hotel Staff and Hotel Manager; the conversation page; rebuilt image.
- **SQL injection review of the Contact form (same day):** read every query on the path (`ContactController`, `CustomerThread::getIdCustomerThreadByToken` and `ByEmailAndIdOrder`, `Order::getByReference`, `CustomerThread`/`CustomerMessage` saves): all values go through `pSQL()`, `(int)` or the object model, so no injectable query was found. Added a second line of defence in `override/controllers/front/ContactController.php`: list values (`message[]=`) are emptied for both POST and GET (before, they caused a fatal TypeError, a 500), and `token`, `contactKey`, `id_contact`, `id_product`, `id_order` and `from` must match a strict pattern or the submission is refused. Free text (message, name) is deliberately not pattern-matched for SQL keywords: ordinary sentences contain them, and escaping is the real protection. **Verified locally:** quote/`OR 1=1`/`UNION`/`DROP TABLE` payloads in message, name, email, token and order id were stored as plain text or refused; `qlo_customer` untouched; smoke test 29 of 29. **Not tested:** automated scanners (sqlmap); other front forms.

### 2026-10-13 — No index.php in the address bar, back office and website

- **Owner:** the .php extension was still visible, "both admin and user".
- **Back office:** its links are generated as `index.php?controller=...` in hundreds of places, so the links are left alone and the address is corrected by the web server. `docker/apache.conf` (rule 25) redirects a GET or HEAD for `/<one folder>/index.php` to `/<that folder>/`, keeping the query string; the same file answers through DirectoryIndex. Guards: only when the browser asked for `index.php` by name (`THE_REQUEST`), so the internal DirectoryIndex lookup cannot loop; POSTs are never redirected, so forms and sign-in are untouched; a separate rule builds an `https://` address when the proxy reports HTTPS, because Apache itself only sees HTTP. The rule is not tied to the admin folder name, which is set at runtime by `ADMIN_DIR`.
- **Why the Dockerfile changed:** rewrite rules in the server-wide config are not applied inside a virtual host unless it inherits them. The first build showed no redirect for that reason. The Dockerfile now adds `RewriteEngine On` and `RewriteOptions Inherit` to the stock `000-default` virtual host.
- **Website:** every link was already friendly except Our Properties. The installer had stored that menu link in `htl_custom_navigation_link` as an absolute address of the install-time domain (`http://<domain>/index.php?controller=our-properties`), so it ignored Friendly URLs and would also have pointed at the old domain after a domain change. `docker/setup-modules.php` rewrites rows still holding that form to the relative `/our-properties` and logs the count (`MODULES_VERSION=17`).
- **Not hidden:** AJAX addresses such as `ajax-tab.php` and the file manager pop-up; they never appear in the main address bar.
- What's New (everyone) and CHANGELOG updated. No guide change: no menu path moved.
- **Verification (local, image rebuilt twice):** `apachectl -t` Syntax OK; back office GET `index.php?controller=AdminLogin` gives 302 to `/admin-salisberg/?controller=AdminLogin`; with `X-Forwarded-Proto: https` and a `salisberg.com` Host the target is `https://salisberg.com/admin-salisberg/?...`; POST to `index.php` is answered directly (200); signed in, Inbox, Bookings, Room Types and the Staff Guide each end on an address without `index.php` and render with no errors; the homepage has no link containing `.php`; `/our-properties` and a room page return 200; `docker/smoke-test.sh` 29 of 29, no PHP errors logged.
- **Not tested:** a real browser session (clicks, form saves, pop-ups) after the redirect; behind Coolify's real proxy; the live server.

### 2026-10-13 — Front desk (Today, Housekeeping), no-rooms warning, walk-in guests, one mailer; Stock, shop pages and 1-Click Upgrade removed

#### Empty room types: checked first, then flagged

- **Tested before changing anything.** Room types 11, 12 and 13 locally have no rooms. On the website they are absent from search results, their page says "sold out", and a direct add-to-cart request is refused ("Rooms are unavailable"). Book Now offers nothing for them. So an empty room type was already unbookable; the 2026-10-12 incident note that a guest could reach a confirmation page was wrong (that incident was the hotel-permission gap found the same day). The Admin Guide warning that repeated it is corrected.
- **What was missing was telling the manager.** `controllers/admin/AdminProductsController.php` (inherited, rule 11): the Total Rooms column shows a red "No rooms" badge (`printRoomCount()`), and `renderList()` adds a warning naming every switched-on room type with no room in service.

#### New module `salisbergfrontdesk` (rule 25: why a new one)

- Nothing existing fits: `salisbergreports` is read-only documents, `salisbergguide` is help, roles and menus. This one changes booking and room state, has its own table and its own two permission rows. It follows the same patterns (tabs created in `ensureSetup()`, installed by `docker/setup-modules.php`, `sb-` naming, CSS from the back office tokens). `MODULES_VERSION=18`.
- **Bookings > Today** (`AdminSalisbergToday`, placed first in the Bookings section, so clicking Bookings opens it): arrivals (due today, due earlier and not arrived, arrived today), departures (in the room and due out today or earlier, left today), rooms in house, unpaid bookings (stay coming, current, or ended in the last 30 days). Rows are limited to the hotels the profile may see (`htl_access`).
- **Check in / Check out** (`changeBookingStatus()`): same rules as the booking page (time within the stay, in before out), a late departure is recorded at the end of the booked stay, and it fires the same `actionRoomBookingStatusUpdateAfter` hook. It is a second implementation beside `AdminOrdersController::changeRoomStatus()`, which reads the request and redirects to the booking and could not be called; the rules are kept identical on purpose. A failed save is reported on the page and logged, never an error page.
- **Bookings > Housekeeping** (`AdminSalisbergHousekeeping`): clean / dirty per room in the new table `sb_room_status`; a room with no row is clean. The module listens to `actionRoomBookingStatusUpdateAfter`, so a check-out from either page marks the room dirty.
- **Out of service is not a new flag.** It is the platform's room status Inactive, which the availability search already excludes, so the Rooms tab and this page cannot disagree (rule 26). A room with a guest checked in cannot be taken out; one with coming bookings can, and the page says so in red. A room closed for set dates (Temporary inactive) is shown as out of service and is changed in its room type.
- **Permissions:** two ordinary rows, View to see and Edit to change. Hotel Staff and Hotel Manager get both once, when the pages are first created.
- `docker/smoke-test.sh` now loads both pages.

#### Walk-in guests

- `controllers/admin/AdminCustomersController.php` (inherited): the form for a NEW guest shows title, first name, last name, email, phone and Enabled only; password, birthday, newsletter, opt-in and the two group fields are on the edit form. `processSave()` supplies the usual customer group when the short form posts none. Email is optional on add: an empty one becomes `guest-<time>-<4 digits>@guest.invalid` (`Customer::NO_EMAIL_DOMAIN`, `Customer::hasNoEmail()` in `classes/Customer.php`).
- `override/classes/Mail.php`: `Mail::Send()` returns without sending when the recipient is such a placeholder. `.invalid` is reserved (RFC 2606) and can never be a real address.
- **Email to the guest** is the platform's booking confirmation, sent by `validateOrder()` when Send mails is Yes on the order form (the default). **SMS is not built**; the owner will add a provider. The places to send from are the hooks `actionValidateOrder` (booking created) and `actionRoomBookingStatusUpdateAfter` (check-in, check-out), reading the phone from the customer.

#### One mailer

- Searched for anything sending mail outside `classes/Mail.php` (`mail(`, Swift transports, PHPMailer, sockets): none. Every email already used the settings under Advanced Parameters > E-mail.
- The only other mail configuration was the IMAP mailbox sync under Inbox (reading a mailbox into conversations). Removed from `controllers/admin/AdminCustomerThreadsController.php`: the options panel, `renderOptions()`, `updateOptionPsSavImapOpt()`, `ajaxProcessSyncImap()`, `renderProcessSyncImap()`, `syncImap()` and the sync call in `renderList()`; and the template folder `customer_threads/helpers/options` (its only content was the Run sync button). The `PS_SAV_IMAP_*` settings and the `customer_message_sync_imap` table are left in the database, unused.

#### Removed (rule 28, with the evidence)

| Removed | Evidence |
|---|---|
| Stock menu: tabs `AdminStock`, `AdminWarehouses`, `AdminStockManagement`, `AdminStockMvt`, `AdminStockInstantState`, `AdminStockCover`, `AdminStockConfiguration` | Deleted on every deploy by `removeMenus()` in `salisbergguide.php` (`$removedMenus`), with every profile's rights to them. The installer still creates them on a fresh install; the same step then deletes them. |
| Their six controllers and the template folders `warehouses`, `stock_mvt`, `stock_instant_state`, `stock_cover` | A search of the whole tree (outside `install/`, caches, translations, docs) for each class name found only the controller itself and its own templates. Not in `ConfigurationTest.php`. The stock classes under `classes/stock/` are used by room and product code and stay. |
| Front controllers `BestSales`, `NewProducts`, `PricesDrop`, `Compare`, `Manufacturer`, `Supplier`; theme files `best-sales.tpl`, `new-products.tpl`, `prices-drop.tpl`, `manufacturer.tpl`, `manufacturer-list.tpl`, `supplier.tpl`, `supplier-list.tpl`, `products-comparison.tpl`, `product-compare.tpl`, `css/comparator.css`, `js/products-comparison.js` | References were: `sitemap.tpl` (the "Our offers" block, removed), `search.tpl` (two includes of `product-compare.tpl`, removed), `FrontController` (loaded the comparison script when the comparator was on; lines removed), `config/xml/themes/default.xml` and the theme's `lang/en.php` (lists and strings, harmless). Content step v6 (`CONTENT_VERSION=6`) deletes their `meta`, `meta_lang` and `theme_meta` rows and sets `PS_COMPARATOR_MAX_ITEM` to 0. |
| `modules/qloautoupgrade` | Not installed, no tab. Remaining mentions are a string passed to `Module::isInstalled()` in `Tools.php` and vendor version-check fields in `Upgrader.php`, neither of which loads the module. |

#### Fixed on the way (inherited, found in the log while testing)

- `classes/Translate.php`: including the PDF translation file of a language that has none logged two warnings per string. It is included only if it exists.
- `classes/order/Order.php`: `$numDays` was unset for items not priced per night, logging a warning and multiplying their quantity by nothing in the invoice tax breakdown. It now starts at 1. **This changes that quantity from 0 to the real quantity on invoices with such items.**

#### Guides (rule 23)

- Staff Guide: menu table (Today, Housekeeping); section 3 (new guest, no email, confirmation email); section 6 rewritten in three parts (Today, Housekeeping, from the booking). Admin Guide: section 1 (the two permission rows), section 3 (no-rooms warning corrected, out of service), section 9 (Inbox row), section 10 (one mail server, placeholder addresses). What's New for all three roles; CHANGELOG.

#### Verification (local, image rebuilt, PHP 8.3)

- **Deploy log:** `Content v6 applied (... shop pages removed: 6)`, `salisbergfrontdesk installed`, `Modules step v18 done`. Database: both tabs under Bookings with Today first, no Stock tabs left, both profiles with view and edit, table and hook present.
- **Today, as SuperAdmin, with stays moved to today by SQL:** counts and rows correct (a late arrival, three arrivals owing GH₵8,580, an overdue departure). Check in recorded the time; a second check-in, a check-out before check-in, and a stay that has not started were each refused with a message; a wrong token changed nothing. Check out recorded the end of the booked stay for the overdue room and the room became dirty through the hook.
- **Housekeeping:** mark clean; out of service refused for a room with a guest in it; allowed for a room with coming bookings, with the red note and `id_status` 2; an invalid state refused; back in service restored `id_status` 1.
- **As the Hotel Staff test account:** Today opens with its buttons; the new-guest form has only the six fields; a guest with no email was created (placeholder address, customer group, bcrypt password); an already-used email was refused; that guest was booked through Book Now and the order form with Send mails on, the booking page opened, and no mail was attempted.
- **Removed pages:** all six shop addresses, in both the old and the friendly form, return 404; home, sitemap, search and contact return 200 with no errors.
- **Bug found and fixed while testing:** the computed columns on Today were blank because the loops modified a copy (`foreach ((array) $rows as &$row)`).
- **Why earlier smoke tests sometimes failed right after a file was copied in:** opcache rechecks files every 60 seconds (`docker/php.ini`), so a hot-patched file is not seen at once. Reload Apache (`apachectl -k graceful`) after copying a PHP file into a running container.
- **Not tested:** a real browser (all of this was driven with `curl`); Hotel Manager on the new pages; a check-out made from the booking page marking the room dirty (same hook, not exercised from that page); real email through SMTP, including an invoice attachment after the two fixes; the Inbox reply and options save after the IMAP removal beyond loading the page; dark mode screenshots of the new pages; a fresh install; the live server.

### 2026-10-13 — "Add new booking"; guest details on the booking form

- **Owner:** Book Now and Add new order are the same job, call both "Add new booking"; and when booking a walk-in, the form should collect name, email and number, use the guest if they exist and create them if not.
- **Labels:** menu entry `AdminHotelRoomsBooking` Book Now to Add new booking (`$menuLabels`, `MODULES_VERSION=19`); the page title in `AdminHotelRoomsBookingController`; the toolbar button on Bookings in `AdminOrdersController` (was "Add new order"); the button on the Today page. The cart button inside the page and the website's Book Now button keep their names.
- **Guest box on the order form** (`admin/themes/default/template/controllers/orders/form.tpl`, an admin template, edited in place): the Customer panel is now Guest, with First name, Last name, Phone, Email address and a "Continue with this guest" button, followed by the existing search box ("Or search for a guest on record"). The "Add new customer" pop-up link is gone from this form; the shortened Add new customer form remains under Customers.
- **Find or create** (`override/controllers/admin/AdminCustomersController.php`, new behaviour so an override): `ajaxProcessFindOrCreateGuest()` validates the four values (`Validate::isName`, `isPhoneNumber`, `isEmail`; a phone or an email is required), looks for a live guest by email, then by phone compared on digits only (at least 7), and otherwise creates one (customer group, random bcrypt password, placeholder address when there is no email). Creating needs the Add right on Customers. The script then builds the same card a search result produces and clicks its Choose button, so everything after that is the existing flow (rule 26).
- **Known limit:** two people sharing one phone number resolve to the more recent record. The page shows the name found and says to click Change if it is someone else.
- Guides: Staff Guide menu table and section 3 (steps rewritten for the Guest box); Admin Guide wording; What's New; CHANGELOG.
- **Verification (local, image rebuilt, as the Hotel Staff test account, driven with `curl`):** the menu shows Add new booking; the order form renders with the five new elements and no template errors; the endpoint created a guest from a phone only, found the same guest from the same number written differently and under another name, found an existing guest by email, created one with an email, refused no contact details and refused a script tag, a bad phone and a bad email with one message each; a request without a token got the platform's invalid-token page. A booking for the new guest was then created and its page opened. `docker/smoke-test.sh` 31 of 31. The PDF translation warnings fixed earlier no longer appear in the log.
- **Not tested:** the Guest box in a real browser (the button, the message box, the automatic Choose click); Hotel Manager; the live server. Seen in the local log and left alone: a warning at `AdminCartsController.php:470` during the cart step, and the invoice logo fetched over `http://localhost:8080` failing inside the container (a local-only address).

### 2026-10-13 — Invoice reviewed and redesigned; receipt; check-in and payment notices

#### What the invoice flow did (read from the code and the database before changing it)

- An invoice (number `#IN000001` onward, table `order_invoice`) is created the first time a booking enters an order state whose `invoice` flag is on. That was: Complete payment received, Processing, Refunded, Partial payment received, Remote payment accepted. **Awaiting payment was not one of them**, so a guest paying cash on arrival, or still to send Mobile Money, had no document at all.
- The PDF (`classes/pdf/HTMLTemplateInvoice.php`, templates in `pdf/invoice*.tpl`, `pdf/header.tpl`, `pdf/footer.tpl`) is attached to the booking confirmation email when the booking already has an invoice, and to a status email when that state's `pdf_invoice` flag is on (only Complete payment received).
- **Faults found on a real invoice:** no logo (it was fetched over the public web address, which the server cannot always reach); the footer printed `Tel: %s`; an invisible "Powered by TCPDF" line; dates as 10/13/2026; amounts wrapping onto two lines; nothing about what had been paid or was still owed; the guest's email missing; payment method shown as the module name.

#### Flow changes

- **Awaiting payment now issues the invoice** (`order_state.invoice = 1` for the state in `PS_OS_AWAITING_PAYMENT`), so every booking has one from the start, showing the balance due. Content step, `CONTENT_VERSION=7`. Undo it under Bookings > Statuses, Awaiting payment, "Allow a customer to download and view PDF versions of his/her invoices". Bookings already sitting in Awaiting payment get their invoice at their next status change.
- **Part payment emails carry the PDF** (`pdf_invoice = 1` for the full and part payment states), `CONTENT_VERSION=8`.
- **The paid document is the receipt:** `getHeader()` titles it Receipt once nothing is owed, Invoice otherwise. Same number, same file.

#### Design (inherited templates, edited in place; licence headers kept)

- `pdf/header.tpl` (shared by every PDF): logo at a fixed width, document name in brand green, number in gold, date, a green rule.
- `pdf/footer.tpl` (shared): address, phone and email on a hairline. The vendor's "electronic version in your account" and Fax lines are gone.
- `invoice.addresses-tab` (From / Billed to, with the guest's phone and email), `invoice.summary-tab` (number, date, booking reference, booked on, status in colour), `invoice.product-tab` ("Your stay": room type and room numbers, check-in, check-out, nights, guests, rate per night, amount), `invoice.payment-tab` (each payment with date, method and reference; then "How to pay the balance" when money is owed), `invoice.total-tab` (lines, Total, Paid, Balance due in red or green), `invoice.style-tab` (brand colours, new classes). The tax, extra-services and other-items tables keep their content and take the same heading band.
- `invoice.tpl`: the bottom block is one row of two cells. The old `rowspan` layout pushed the last line onto a second page.
- **Data added in `HTMLTemplateInvoice`:** `getBookingSummary()` (guest, totals, paid, balance, status, payments, how to pay from `Salisbergpay::getAvailableMethods()`, `getMomoDetails()`, `getBankDetails()`), `dateInWords()` (13 Oct 2026, the same everywhere), room numbers and worded dates on each stay line.
- **Fixes at the source:** `HTMLTemplate::getLogo()` returns the file path; `PDFGenerator` switches the TCPDF line off; `Translate::getPdfTranslation()` fills `%s` placeholders when a language has no PDF translation file.

#### Notices to the guest (`modules/salisbergfrontdesk`, `MODULES_VERSION=20`)

- **Check-in** (`actionRoomBookingStatusUpdateAfter`, first room of a booking only): an email from the module's own template (`mails/en/checkin.html`, `.txt`) with the invoice or receipt attached, and a text.
- **Paid in full** (`actionOrderStatusPostUpdate`, new state with `paid = 1`): a text. The platform's own payment email already carries the PDF.
- **Texts** go through `Hook::exec('actionSalisbergSendSms', array('phone', 'message', 'id_order', 'reference'))`. No module listens to it yet, so nothing is sent; a provider module registers that hook and sends. A mail or text failure is logged and never blocks a check-in.

#### Found and left for the owner (data, not code)

- **Two installer sample charges are added to every booking automatically:** "Room Maintenance Fees" and "Internet Handling Charges", GH₵250 each, shown on the invoice as Fees. Rooms & Services > Manage Service Products.
- **A 4% sample tax** is applied to rooms and fees.
- The hotel address and phone on the invoice are still the installer's (Montgomery, Alabama; 0987654321).
- **Marking a booking Complete payment received records the full amount as a payment by itself.** A payment also added by hand is then counted twice (seen in testing: paid GH₵17,160 on a GH₵8,580 booking). Staff Guide section 5 now warns about it.
- The status and confirmation emails are still the vendor's design and wording ("order", "powered by Webkul"); only the new check-in email is in the brand style.

#### Guides (rule 23)

- Staff Guide section 5 (the invoice, what the guest is sent, record a payment once). Admin Guide section 10 (table of what is sent when; the text hook, SuperAdmin only). What's New; CHANGELOG.

#### Verification (local, image rebuilt; PDFs opened and read)

- Before/after of the same paid invoice; an unpaid one (NOT YET PAID, balance in red, how-to-pay block with the test Mobile Money and bank details); the same booking after payment (headed RECEIPT, two payments listed, balance GH₵0.00). Each is one page, with the logo, and no amount or heading wraps.
- With a throwaway listener module installed in the local container only (it logged instead of sending): checking in the first room of a booking produced the welcome text and an email with `#IN000004.pdf` (486 KB) attached; checking in a second room of another booking produced nothing more; marking a booking paid produced the payment text and the platform's payment email with `#IN000007.pdf` attached. No PHP errors or warnings logged. `docker/smoke-test.sh` passes.
- **Not tested:** real delivery of any email (this machine has no mail server; the send step itself failed with a sendmail error after the message was built); a real SMS provider; the invoice with discounts, extra services, refunds or several pages of rooms; the credit slip and other PDFs with the new shared header and footer; the guest's own download from the website; the live server.

#### Follow-up, same day: the Invoices page

- **Before:** Bookings > Invoices had no list at all, only two bulk-print forms and a block of numbering settings.
- **Now** (`controllers/admin/AdminInvoicesController.php`, inherited; new template `admin/themes/default/template/controllers/invoices/list.tpl`): `renderInvoiceList()` shows up to 200 invoices, newest first, limited to the hotels the profile may see, with number, date, booking (link), guest and phone, stay, total, paid, still to pay, a status label and a PDF button (labelled Receipt when settled). Filters, all validated: free text (invoice number, booking reference, guest name, phone; `%` and `_` removed, the rest through `pSQL()`), status against a fixed list, two dates against a pattern and `Validate::isDate`. Four totals for the rows shown. Stock Bootstrap classes only, no new stylesheet.
- The two bulk forms are kept under clearer titles. The options block (prefix, numbering, model, cache) is rendered for SuperAdmins only.
- Guides: Staff Guide menu table and section 5; Admin Guide section 10; What's New; CHANGELOG.
- **Verification (local, image rebuilt):** as SuperAdmin the page lists the 7 local invoices with correct totals; filters by status, by guest name and by invoice number return the right rows; a script tag as status, an impossible date and a quote and percent sign as search text give an empty list with no error. As Hotel Staff the list shows and the options block does not.
- **Not tested:** more than 200 invoices; a profile limited to one of several hotels; the two bulk-print forms after the change (their code is untouched); a real browser.

### 2026-10-14 — Charges & Tax page; payments cannot be counted twice

#### Charges & Tax (Rooms & Services)

- **Owner:** provide a way to tick or untick the two sample charges and the sample tax.
- **What the samples really do** (read from the database): "Room Maintenance Fees" GH₵250 per room per night, `price_addition_type` 1, i.e. added inside the room rate the guest sees (a GH₵200 room shows and is charged GH₵450); "Internet Handling Charges" GH₵250 per room per stay, shown as a separate fee. Both `auto_add_to_cart = 1`, hidden from guests (`show_at_front = 0`). The tax is the installer's "US-AL Rate (4%)" rule group on 12 room types and charges; it applies because the hotel address is still the sample Alabama one.
- **Where (rule 25):** a new page in the existing `modules/salisbergpay`, because it is about what the guest pays. `controllers/admin/AdminSalisbergChargesController.php`, `views/templates/admin/charges.tpl`, and in `salisbergpay.php`: `ensureChargesPage()` (menu entry under Rooms & Services; Hotel Manager given view and edit once, when created), `getAutoCharges()`, `setChargeAutoAdded()`, `getTaxesInUse()`. Created by `docker/setup-modules.php` after the profiles exist (`MODULES_VERSION=21`).
- **What the switches change:** a charge's tick is `auto_add_to_cart` on `product` and `product_shop`, and only for products the page lists (service products that are added by themselves or hidden from guests); optional extras are refused. The tax tick is the platform's own `PS_TAX` (Localization > Taxes, "Enable tax"). New bookings only; bookings already made keep their lines.
- **Left as they were:** both sample charges and tax are still ticked. Removing them is a pricing decision for the owner.

#### Payments counted twice

- **How it could happen:** marking a booking Complete payment received records a payment for what the *invoice* has not been paid (`OrderInvoice::getRestPaid()`), counting only payments linked to that invoice. A payment recorded before the invoice existed, or without the link, was then taken a second time. Recording the same payment twice by hand was also possible.
- **Fixed at the source:** `classes/order/OrderHistory.php` caps that automatic payment at what the booking still owes (`total_paid_tax_incl - total_paid_real`); `controllers/admin/AdminOrdersController.php` (Add payment) refuses an amount above what is still owed, converted to the booking's currency, with a message naming the amount. Refunds (negative amounts) are unchanged. Since 13 October every booking has its invoice from the start, so payments are linked as they are recorded.
- `admin/themes/default/template/controllers/orders/helpers/view/view.tpl`: the red "Warning X paid instead of Y" shown for every part payment now reads "Paid so far: X of Y. Still to pay: Z" in amber, and is red only when more than the total has been recorded.

#### Guides (rule 23)

- Admin Guide section 5 (new "Charges added to every booking, and tax", with the sample charges spelled out) and section 7 (managers pointed to the switch). Staff Guide section 5 (the double-count warning replaced by what the system now does). What's New 14 October (everyone, and manager); CHANGELOG.

#### Verification (local, image rebuilt)

- Charges & Tax as SuperAdmin: lists the two charges with how each is charged and the tax group in use. Unticking Room Maintenance Fees changed the website price of Standard Room 1 from GH₵450 to GH₵200; ticking it again restored GH₵450. Trying to switch an optional extra (Airport Shuttle) was refused. The tax switch saved and showed its message; its effect on a booking total was not checked. Everything was put back as it was.
- As the Hotel Staff test account: a new unpaid booking (GH₵832) got invoice #IN000008 at once; a GH₵100 payment was recorded and linked to it; a GH₵999,999 payment was refused ("more than the GH₵732.00 still owed"); marking it Complete payment received recorded exactly GH₵732, total paid GH₵832.
- **Not tested:** the tax switch on a real booking total; the Charges & Tax page as Hotel Manager; a booking in another currency; the new booking-page notice in a browser.

### 2026-10-14 — Layout on tablets, laptops and phones; Charge tax switch on the booking form

#### How the faults were found

- A checker (outside the repository) opens pages in headless Chrome at 390, 768 and 1024 wide, lists elements that run past the right edge (not inside a scrolling or clipped box) and visible elements that overlap, and saves a screenshot of each. Run over 7 website pages and 14 back office pages, before and after. Overlaps that are by design were set aside after looking at the screenshots: the password eye inside its field, the menu fold arrows on their rows, the switch knobs, the gallery's scroll button on its thumbnails, calendar day numbers under booking bars.
- **Lesson:** the browser profile used by the checker cached the back office stylesheet between runs (it is served with no version in its address), so one run showed old rules. Start each run with a clean profile. Returning staff will see the same for a while after a deploy, until their browser refetches `overrides.css`.

#### Website (`salisberg.css` `?v=15` and inherited theme files)

| Fault | Fix |
|---|---|
| Location field: the pin icon covered "Hotel Location" | `css/global.css`: `.input-location`'s left padding was out-ranked by `.form-control`; the selector is now `.form-control.input-location` (rule 11) |
| Search filters and the room booking panel squeezed into a third of a tablet screen (dates cut off, prices wrapping) | `header.tpl` (left and centre columns) and `product.tpl` (room page columns): `col-sm-*` became `col-md-*`, so below 992px they stack, filters and booking panel first |
| Phone homepage scrolled sideways by 16px | a room card's flex item took its content's width; `min-width: 0` on it |
| A dark strip along the right edge at every width | the closed menu panel's shadow; the shadow is now only on the open panel |
| "/Per Night" split over two lines beside the price | `css/category.css`: `.rm_price_txt` does not wrap |
| Amenities ran together ("FanTV") | `docker/setup-room-types.php` wrote the amenity list as HTML into the short description, which the platform stores as plain text. It now writes "Fan, TV"; a pass rewrites the six room types where the text is still in the run-together form (`ROOM_TYPES_VERSION=2`, logged per room) |

#### Back office

| Fault | Fix |
|---|---|
| Page titles covered by the buttons beside them ("Order PBSTLDFNA - Salis[View Hotel]", "Edit: Sta[Preview]") | New `modules/salisbergguide/views/js/page-head.js` (loaded by `addInterfaceAssets()`): measures each title bar (a booking draws two, one over the other) and, when the title's text and the buttons would meet, adds `sb-head-stacked` to that bar and moves the content down by the extra height. `overrides.css` section 6 puts the buttons on a row under the title. The stock rule holds the button list as `display:block !important`, so the buttons are inline blocks rather than flex items |
| Lists and tables wider than the space beside the menu pushed the whole page sideways (Bookings 1733px at a 1024px window, Customers 1865px, Room types 1602px) | `overrides.css` section 7: the existing Stats-only rule generalised to `.table-responsive`, tables directly in a panel, and, from 992px, `.table-responsive-row`; each scrolls inside its box. Below 992px the stock theme already turns lists into stacked cards |
| Figure tiles at the top of Bookings and of a booking overlapped ("GH₵22,200.00" outside its tile, "Today"/"30 Days" over the labels) | `admin-theme.css` (inherited): tiles were a fifth of the width each whatever the space; now `flex: 1 1 190px` with wrapping, and the two width-by-window media rules removed |
| Phone (below 768px): fold arrows drawn in the icon rail; Staff Guide tables and the dashboard occupancy chart wider than the screen | arrows hidden in that range (`overrides.css` section 11); `guide.css` lets a guide table scroll inside itself on phones; the chart's SVG is limited to its box |

#### Charge tax switch on the booking form

- **Owner:** a tick for tax, on by default, that staff can untick for a booking; the manager or admin can turn it off.
- **Booking form** (`admin/themes/default/template/controllers/orders/form.tpl`, beside Send mails): "Charge tax" Yes/No, Yes by default, shown only when `sb_tax_choice` is set.
- **New `override/controllers/admin/AdminOrdersController.php`:** `renderForm()` sets `sb_tax_choice`; `postProcess()`, when the booking is submitted with `sb_charge_tax=0` and the choice is allowed, sets `PS_TAX` to 0 for that request only with `Configuration::set()` (nothing saved). The platform's own tax code (`TaxRulesTaxManager::getTaxCalculator()`, `Tax::excludeTaxeOption()`) then builds the booking without tax.
- **Who controls it:** `Salisbergpay::taxChoiceAllowed()` is true while tax is on (`PS_TAX`) and the new setting `SBPAY_TAX_PER_BOOKING` (default 1, created by `ensureSettings()`) is on. Rooms & Services › Charges & Tax has the tick "Let staff leave tax off a single booking", shown while tax is on. `MODULES_VERSION=22`.
- **Known limit:** the totals shown on the form above the switch still include tax; the help text says so. The booking, the full-payment amount and the invoice do not.

#### Guides (rule 23)

- Staff Guide section 3 (the Charge tax switch). Admin Guide section 5 (the new tick). What's New 14 October (everyone: the switch and the layout; manager: the tick). CHANGELOG.

#### Verification (local, image rebuilt)

- **Checker after the fixes:** website at 390, 768 and 1024: no page scrolls sideways, no unexpected overlaps; screenshots reviewed (search results at 768 and 1024, room page at 768). Back office at 768 and 1024 on 14 pages: no page scrolls sideways and no unexpected overlaps; screenshots reviewed (Bookings, a booking, the room type form). At 390, left as they are: the booking page's "Menu" button over its status badge, and the dashboard date button over its hidden field.
- **Tax switch, as the Hotel Staff test account:** the switch is on the form; the same room type booked with Yes came to GH₵728 (GH₵700 + GH₵28 tax), with No to GH₵700, invoice GH₵700 / GH₵700; `PS_TAX` was still 1 afterwards. With the manager's tick off (set as SuperAdmin on Charges & Tax) the switch was gone from the form, and a booking posted with No anyway was charged tax (GH₵728). The tick was put back on.
- `docker/smoke-test.sh` 31 of 31.
- **Not tested:** a real browser on a real tablet or phone; Safari and Firefox; right-to-left; back office pages outside the 14 checked; the switch as Hotel Manager; the live server.

### 2026-10-14 — Add new booking: one click per room, and the next step always in view

- **Owner:** the back office booking flow is hard, especially choosing the room and the occupancy, and then having to open the cart, which staff sometimes do not see.
- **Seen in screenshots taken as the Hotel Staff test account before changing anything:** (1) the guests chosen in the Booking Form were not used by the room rows; clicking Add To Cart on a row only opened that row's own guest box and added nothing, so every room needed the guests entered twice and a second click; (2) after Search the room list sat below the calendar and the counts, out of view; (3) the only way on was a small "Cart" button in the calendar panel's corner, then "Book Now" in a pop-up.
- **Changes** (inherited `hotelreservationsystem` files, edited in place, rule 11):
  - `views/js/admin/hotel_rooms_booking.js`: `useSearchOccupancy()`, called by both Add To Cart handlers (available and partially available rooms) before the row's guests are read. A row with no guests chosen takes the first room of the Booking Form's occupancy: adults, children and each child's age (the age boxes are built by triggering the platform's own change handler). A row whose guests were set by hand is left alone. `updateBookingBar()`, called from `refreshCartData()` and on page load, keeps the new bar's count, total and link in step with the cart.
  - `views/templates/admin/hotel_rooms_booking/helpers/view/view.tpl`: the bar (`#sb-booking-bar`: rooms chosen, total before tax, Review selection opening the existing cart window, Continue to guest and payment going to the same address as the cart's old Book Now button); the room list wrapped in `#sb-room-results`; after a search (the search redirects to an address carrying `date_from`) the page scrolls to it.
  - `_partials/booking-cart.tpl`: the cart window's "Book Now" now reads "Continue to guest and payment".
  - Styles in `admin/themes/default/css/overrides.css` (section 7): the bar is fixed to the bottom of the content area, beside the side menu, and the page gets room below so nothing hides under it.
- **Guide (rule 23):** Staff Guide section 3, steps rewritten. What's New (everyone); CHANGELOG.
- **Verification (local, image rebuilt, headless Chrome as the Hotel Staff test account):** after Search the page scrolled to the room list (837px down; screenshot reviewed); one click on Add To Cart added the room (cart 0 to 1, row button became Remove) and the bar showed "1 selected for this booking, Total before tax: GH₵700.00"; Continue to guest and payment opened Bookings > Add new for that cart. A search for 2 adults and a 7-year-old, then one click, stored adults 2, children 1, ages ["7"] for the room. Leftover test carts were removed from the local database.
- **Not tested:** a real browser by hand; a booking form with two rooms of different guests (each row takes the first room's guests; a different second room is set in its own Guests box); partially available rooms (same function, not exercised); phone width; the live server.
- **Menu: the list of bookings is now "Reservations" (2026-10-14).** `$menuLabels` in `modules/salisbergguide/salisbergguide.php`: `AdminOrders` now lists the names it may still carry (`Orders` or `Bookings`) and becomes `Reservations`; `renameMenuEntries()` accepts a list (`MODULES_VERSION=23`). The section `AdminParentOrders` stays "Bookings". Staff Guide menu table and section 4 and Admin Guide section 14 say `Bookings > Reservations`. The page's own heading still reads "Orders" (vendor controller text, as recorded 2026-10-06). **Verified locally:** after a rebuild and a manual run of `setup-modules.php` the tab reads Reservations in the database and in the menu. **Not tested:** the live server, where the rename acts only while the entry still reads Orders or Bookings.

### 2026-10-15 — Room board is the booking entry; Today moved to Dashboard; unused menus removed

**What**

- `Bookings > Add new booking` is now the `AdminSalisbergRoomBoard` tab. The Reservations toolbar button opens it, and the old `AdminHotelRoomsBooking` tab is hidden. Direct visits to the old room-search controller and `AdminOrders&addorder` redirect to Room board; its AJAX room-reallocation and swap endpoints remain available for existing bookings.
- Future arrivals now enter the same guided Guest, Payment and Confirm workspace as walk-ins. Completion uses `reserve`, while only a ready same-day room offers `checkin`. The old visible cart/search/form pipeline is no longer used; the inherited Cart and hotel-cart records remain the booking engine's internal staging model.
- The full Today workspace is rendered from `salisbergfrontdesk` on `dashboardTop`. Its hidden `AdminSalisbergToday` controller still validates Check in and Check out posts, then redirects back to Dashboard. The smaller stock `dashguestcycle` Operations Today zone is unhooked; its top summary badges remain.
- Channel Manager is disabled on every module setup. `AdminQloappsChannelManagerConnector`, `AdminZones` and `AdminSlip` are inactive menu tabs. Zones and credit-slip classes/tables were deliberately retained because country/tax/address and refund/document workflows share them.
- The CSRF token remains in back-office links. Removing it would weaken the inherited admin request protection and make refreshes/forms unreliable; this request was declined rather than replaced with a cosmetic URL rewrite.
- Removed the custom sticky cart bar, result auto-scroll, search-occupancy copier, old `AdminOrders` per-booking tax field, and `salisbergguide::grantCartEditing()`. They served only the retired visible cart flow. `AdminCarts`, the hidden room-booking controller, and its base assets remain because dashboard/customer cart links and booking reallocation endpoints still reference them.
- `MODULES_VERSION=24`. Staff Guide sections 2, 3 and 6, Admin Guide sections 1 and 15, What's New, and `CHANGELOG.md` describe the resulting workflow and menu.

**Why**

- Staff had two competing booking entry points and the visible Add new booking route still opened the cart pipeline the Room board was meant to replace. Daily actions also belonged on the Dashboard instead of a separate Today page. Channel Manager, Zones and Credit Slips add navigation that this hotel does not use, but deleting their shared implementation would create avoidable breakage.

**Verification**

- Built and restarted the local image. Module setup v24 completed and disabled `qlochannelmanagerconnector`; the application log contained no new PHP warning or error.
- Confirmed in the database that Room board is the visible Add new booking tab, Today and the legacy room-booking controller are active but hidden, Channel Manager/Zones/Credit Slips are inactive, `salisbergfrontdesk` owns `dashboardTop`, and `dashguestcycle` is no longer attached to `dashboardZoneTwo`.
- Authenticated checks confirmed Dashboard renders Today at the desk, both Add new entry points open Room board, direct legacy booking routes redirect to Room board, and the removed menu items do not render.
- `docker/smoke-test.sh` passed all 30 checks. Repository-wide symbol searches after the cleanup found no code references to `sb-booking-bar`, `sb-room-results`, `useSearchOccupancy`, `updateBookingBar`, `sb_charge_tax`, or `grantCartEditing` outside historical records.

### 2026-10-15 — Front-desk booking navigation simplified

**What**

- The Room board orders available ready rooms first, then other available rooms, and puts unavailable rooms last. It adds Available, Ready now, Needs cleaning and All rooms filters; room-number/type search; compact cards; a lowest-priced ready-room recommendation; availability counts; and a fixed Continue action on tablets and phones.
- Active guided-booking drafts belonging to the signed-in employee appear on the Room board with Resume and Discard. Discard removes the unused hotel-cart rows and cart in a database transaction, then marks the draft discarded so held room availability is released cleanly.
- The visible guide is three steps: Guest, Payment and Confirm. Stay remains an edit screen reached from the summary, while the existing internal step numbers remain unchanged to avoid a data migration.
- Guest details survive validation errors and errors appear beside their fields. Search ranks exact phone and email matches first, Enter selects the first result, and a duplicate found while entering a new guest can be used directly. Changing away from an existing guest clears its details instead of silently carrying them into a new record.
- Payment asks for Paid in full, Part payment or Pay later first. Pay later does not require or store a payment method; received money still requires Cash, Mobile Money or Bank transfer and confirmation. Cash tender follows the full or partial amount until the employee edits it.
- Confirm and success remove repeated information while keeping the stay, guest, payment, balance and next actions visible. `salisbergfrontdesk` is version 1.3.1. Staff Guide section 3, What's New and `CHANGELOG.md` describe the changes.
- Hotel Staff testing found that final confirmation could not execute either transaction lock: `Db::getRow()` and `getValue()` appended `LIMIT 1` after the supplied `FOR UPDATE`, producing invalid MySQL `FOR UPDATE LIMIT 1`. Both the draft and room locks now use an explicit uncached `SELECT ... LIMIT 1 FOR UPDATE`, preserving the concurrency protection instead of removing it.

**Why**

- Front-desk staff need to find a suitable room quickly, resume interrupted work without retyping it, and make the fewest decisions needed to complete a correct booking. Releasing abandoned drafts also prevents an unfinished internal cart from making a room appear unavailable.

**How / safety**

- Draft listing and discard are restricted to the current employee, and discard is a token-protected POST action through the existing admin controller. Room availability is checked again by the existing finalisation path before a reservation is created.
- Finalisation locks the employee-owned draft and selected room inside the existing transaction before checking availability and creating the order. Locking selects bypass the query cache.
- No schema was added: the existing draft JSON and internal cart remain the single staging model. Government ID type and number were not added because collecting sensitive identity data needs an explicit hotel requirement, retention period and access policy first.

**Verification**

- Rebuilt and restarted the local Docker image, then rebuilt it again after the locking correction so the fix is in the deployable image. The authenticated `docker/smoke-test.sh` suite passed 30 of 30 checks on PHP 8.3.35 and MySQL 8.0.46, including the Dashboard, Room board, guides and application log; no PHP warning or error was logged.
- An authenticated UI check opened the Room board, found search, filters and the recommended room, created a draft, rendered Guest/Payment/Confirm, listed the draft under Unfinished bookings, resumed it, then discarded it. The database showed `status=discarded`, no cart and zero hotel-cart room rows.
- A second draft submitted invalid guest details and retained the typed values and note with inline first-name and phone errors. It then selected an existing search result, saved Pay later without a payment method, reached Confirm with no payment recorded, and was discarded. Its cart and room hold were also absent afterward.
- The first completion attempt as the Hotel Staff test employee exposed the two invalid locking queries. No order or payment was created; the failed draft was discarded, its cart and room hold were absent, its unused guest was removed, and the employee password hash was restored.
- After correcting both locks, the same Hotel Staff profile completed future reservation `MJATYEKLB` (order 16) for room GR-105, 12–14 February 2027. The success page rendered, View stay opened without a permission error, and the reference appeared in Reservations. Database checks showed employee 3 (`staff.test@example.com`), complete draft 6, Awaiting payment, Pay later, GH₵416 total, GH₵0 paid, and allotted/reserved booking status. The employee password hash was restored after the test.
- PHP syntax checks passed for the service, both controllers and module class; JavaScript syntax checks passed for Room board and walk-in scripts; `git diff --check` passed.
- Not tested: visual interaction in a real browser, the Hotel Manager profile, a same-day check-in or confirmed payment, or the live server.

### 2026-10-16 — Room board count and selected-room layout

**What**

- The count for each room type now sits in a small badge immediately beside the type name. `room-board.js` recalculates it after every status filter or room search, so it describes the rooms currently shown rather than the unfiltered total.
- The selected-room column is 330–360px instead of 290px. Its detail grid now lets both columns shrink and wraps long values, preventing capacity and nightly rates from crossing the centre or right edge.
- Check-in and check-out dates use `15 Oct 2026` consistently. Guest wording replaces the ambiguous `1 + 0`, Capacity is named on room cards, and a selected card has both a visible check mark and `aria-pressed=true`.
- The estimated stay total is a separate highlighted row. The nightly-rate breakdown is hidden for a flat-rate stay and shown only when nightly prices differ.
- Below 1200px the board becomes one column and the existing fixed Continue action appears, instead of squeezing the room grid and summary beside the 304px back-office menu.

**Why**

- At the width shown by the owner, `justify-content: space-between` made a room count look detached from its heading. The 290px summary and its max-content value column also caused long details to collide and overflow.

**Guide**

- Staff Guide section 3 now explains the filtered room-type count, simplified selected-room summary and conditional nightly breakdown. What's New and `CHANGELOG.md` describe the visible change.

**Verification**

- Rebuilt and restarted the local Docker stack after the Room board code change. `docker/smoke-test.sh` passed 30 of 30 checks on PHP 8.3.35 and MySQL 8.0.46, including the authenticated Room board, with no PHP error or warning in the application log.
- PHP syntax checks passed for `SalisbergWalkIn.php` and `AdminSalisbergRoomBoardController.php`; the Room board JavaScript syntax check and `git diff --check` passed.
- A temporary headless-Chrome visual checker did not start its debugging endpoint and was removed with its temporary browser profile. The final layout was therefore not claimed as visually checked; a real-browser screenshot at the owner's working width remains to be reviewed.
- The Staff Guide, What's New and change-record edits were made after that successful build. A second rebuild was attempted, but Docker Desktop was no longer running (`dockerDesktopLinuxEngine` pipe absent), so those documentation-only edits were not rechecked in the container.

### 2026-10-16 — Room board: selected room first; "Bookings" as the one name; New booking out of the side menu

**What**

- **Room board** (`modules/salisbergfrontdesk`): the Selected room panel is now first in the page and first in the grid (`.sb-board-layout` columns swapped, panel moved before `.sb-room-groups` in `room_board.tpl`), so it stays on top when the board collapses to one column. The primary button reads **Continue to guest** in both places (panel and the fixed phone bar) for same-day and future stays, since the next screen is Guest either way; the old "Book walk-in" wording is gone. Room-type headings read `Standard Room 1 — 5 rooms`: heading, dash and count badge on one row, and `room-board.js` still recalculates the count after a filter or search.
- **Sidebar:** the Room board tab (`AdminSalisbergRoomBoard`, named "New booking") and the legacy `AdminHotelRoomsBooking` tab are active but parented to `-1`, so they are out of the menu and still open by link (`configureTabs()` in `salisbergfrontdesk.php`). The way in is the **New booking** button on Bookings > Bookings (`AdminOrdersController::initPageHeaderToolbar()`), the Today panel and the Staff Guide link. Direct requests to the old cart form (`addorder`, `submitAddOrder`) redirect to the Room board through `override/controllers/admin/AdminOrdersController.php`.
- **"Bookings" everywhere a person reads it.** `$menuLabels` in `salisbergguide.php` now renames `AdminOrders` from Orders or Reservations to **Bookings** (a list of old names is accepted), and adds Order messages to Booking messages, Preferences > Orders to Bookings, and the refund rule and request pages to Refund rules and Refund requests. Visible text was changed in the inherited booking page heading, the customer view, overbooking panels, refund screens, order statuses and messages, search results, dashboard and statistics modules (`dashgoals`, `dashguestcycle`, `dashproducts`, `dashtrends`, `dashperformance`, `stats*`), the guest account pages of the website, and, found in this pass, the top-bar notification panel (`admin/themes/default/template/header.tpl`: Latest Bookings, Show all bookings, Booking number, View this booking). `MODULES_VERSION=25`.
- **Left unchanged on purpose:** `AdminOrders`, `id_order`, `qlo_orders`, table, hook, controller and class names, URLs and the `orders` parameter of integrations (rules 12 and 29); renaming them would break the inherited booking engine. The `Order`-named files and hooks are not user-visible.
- **Follow-up, same day (owner: room list should be the main part, aligned, easy to navigate):** `frontdesk.css` columns are `minmax(290px,1fr) minmax(0,2fr)` (a third and two thirds). Measured at 1440 and 1280 wide: panel and room list start at the same height. The panel had been pushed down 74px because the stock `.panel` rule (`position: relative`) beat our `position: sticky; top: …`, which turned the offset into a shift; its rule (and `.sb-stay-summary`) now carries `#content.bootstrap .sb-frontdesk`, and after scrolling 900px the panel and its Continue button stay on screen. The same stock rule `#content.bootstrap h3:not(.modal-title)` (border-bottom, 2.2em height, negative margins) was drawing a line through the room-type headings and offsetting them, so every front desk `h3` rule (draft panel, room groups, selected room, steps, summary) is scoped above it with `height: auto; line-height: 1.3`; the count is a pill badge. The search icon covered the placeholder (stock input padding won); the rule is scoped the same way. Removed as redundant: the page's own `Front desk / Room board` heading (the page title already says New booking), the `.sb-board-note` banner (dates and guests are already in the form and the panel) and the sentence under Unfinished bookings, with their dead CSS (rule 28). `.sb-desk-panel h3` (Today panels on the Dashboard) has the same stock-rule problem and was left alone because it was not asked for.
- **Button renamed again at the owner's request:** the Selected room button and the phone bar button read **Book now** (was Continue to guest). It still opens the guided Guest step; the booking is made on Confirm. Staff Guide section 3, What's New and CHANGELOG match.
- Guides (rule 23): Staff Guide menu table and sections 2, 3, 4, 6; Admin Guide paths (`Bookings > Bookings`, section 14); What's New and `CHANGELOG.md` (16 October).

**Verification (local, image rebuilt, PHP 8.3.35 / MySQL 8.0.46)**

- `docker/smoke-test.sh`: 30 of 30, before and after the notification-panel text change. The changed `header.tpl` was copied into the running container rather than rebuilt; the dashboard then showed Latest Bookings and Show all bookings and no Latest Orders.
- Tabs after the deploy step (`Modules step v25 done`): AdminOrders "Bookings" (parent 9), AdminParentOrders "Bookings", AdminOrderMessage "Booking messages", AdminOrderRefundRequests "Refund requests"; Room board, Today and the legacy room-booking tab have `id_parent -1`.
- As the Hotel Staff test account (driven with Node/fetch, every request in the order the page makes it): the sidebar lists no Add new booking, Today or Room board entry; Bookings opens with the heading "Bookings" and the New booking button; the Room board has Selected room before the room list, four headings with count badges and two "Continue to guest" buttons; a **future** booking (room DR-101, 15–17 Dec 2026) went Room board > Guest > Payment (Pay later) > Confirm with "reserve" and a draft marked complete, and a **same-day** booking offered and recorded "checkin" (room status 2); both appear in the Bookings list and the checked-in booking's page opens without a permission error. No PHP error or warning in the pages visited.
- A scan of the visible text of Dashboard, Bookings, Invoices, Customers, Refund requests, Inbox, Housekeeping and the Staff Guide found "Order" only in the notification panel, since fixed in `header.tpl`.
- A draft started from the Room board was listed under Unfinished bookings with Resume, then discarded: status `discarded`.
- **Not tested:** the notification panel text in a rebuilt image; a real browser (visual layout of the Room board at 1200px and below); a part-paid or paid booking in this pass; Hotel Manager; the live server, where the rename acts only while a menu entry still carries one of its old names.
- Test data left in the local database: bookings DSWMBYAHA and DWCIDNINW (guests "Flow Testerfuture" and "Flow Testertoday"). The Hotel Staff test account's password was set to a temporary value for the test and its old hash restored afterwards.

### 2026-10-16 — Date boxes open the calendar on any click

- **Owner:** on New booking, staff had to find the small calendar icon in Arrival and Departure; a click in the empty part should open the calendar too.
- **What:** `openCalendarOnClick(field)` added to the two scripts that already drive these fields, beside their existing `change` listeners: `modules/salisbergfrontdesk/views/js/room-board.js` (Arrival and Departure on New booking) and `walkin.js` (the stay screen of the guided booking). A click on the date box calls `showPicker()`; a click on the right-hand 34px (the icon) is left to the browser so the picker is not opened twice; browsers without `showPicker()` are unaffected; typing still works. Nothing in the repository opened the picker before (no `showPicker` anywhere), so this extends those scripts rather than adding a file.
- **First version, withdrawn the same day:** it was a new shared file `modules/salisbergguide/views/js/date-picker-click.js` loaded on every back office page. The owner asked why a new script and not the existing code; the fair answer was that I had looked only for an existing picker helper, not at the scripts that already handle these two fields, and the request concerned only those. The file and its loader line in `addInterfaceAssets()` were deleted. Side effect to know: other back office date fields are not affected, and the 8-line helper now exists in both files (as `dateAtMidnight` and `formatDate` already did); merge them if a third use appears.
- Staff Guide section 3 step 2, What's New and CHANGELOG updated.
- **Verification (local, headless Chrome, real mouse events through the DevTools protocol, files copied into the running container):** a click on the right-hand part of the empty Arrival field called `showPicker()` once; a click on the icon called it zero times. `node --check` on both scripts passed; `docker/smoke-test.sh`: 30 of 30.
- **Not tested:** that the popup is drawn (headless Chrome does not show it); Firefox and Safari (Safari before 16 has no `showPicker()`, so nothing changes there); the walk-in stay screen click (same helper, not clicked); a rebuilt image.

### 2026-10-16 — Whole booking flow run for Hotel Staff and for the administrator; one inconsistency fixed

- **Owner:** run the booking flow to the end, as admin and as Hotel Staff.
- **Method:** a script (outside the repository) signs in as each role over HTTP and does exactly what the pages do: Bookings > New booking button, Room board with dates, Book now (the guided booking opens), Guest step, Payment step, Confirm, then reads the database. Four scenarios per role: future stay, new guest, Pay later (reserve); same-day, new guest, full payment in cash with change (check in); future, an existing guest found by search, part payment by Mobile Money with a reference (reserve); same-day, new guest with an email, full bank transfer with a reference (check in).
- **Result: 30 of 30 checks passed for the administrator and 30 of 30 for Hotel Staff** (sign-in, no New booking in the sidebar, Today panel on the Dashboard, Bookings heading, the button, each step accepted, correct final action offered, booking created, listed under Bookings). Database for all eight bookings: Pay later gives Awaiting payment with GH₵0 paid; full cash, full bank and the cash/bank same-day bookings give Complete payment received for the whole total and room status checked in (2); the part MoMo bookings give Partial payment received with GH₵138 of GH₵416 and the MoMo reference stored as the transaction id; payment rows carry the method name and reference; invoices 21 to 24 exist (Awaiting payment now issues one); no draft is left active.
- **After booking, as Hotel Staff:** the Dashboard Today panel offers Check out; a check-out posted to the Today controller set the booking to checked out (3) and the room to Needs cleaning; the Housekeeping page lists the rooms with their controls; the booking page opens without a permission error and shows the cash payment and total; Bookings > Invoices lists the booking and its invoice PDF downloads as a valid PDF (486 KB). The PDF was not opened visually this time (no PDF renderer on this machine).
- **Fault found and fixed:** on the Room board a room counted as occupied only when a checked-in guest's stay covered today, while the Guest-Payment-Confirm steps (`currentRoomSummary()`) and the Today panel counted any guest still checked in. A guest past their departure date therefore left the board saying Ready and the Confirm step offering only "awaiting room" (seen on room GR-104 after an old test booking was never checked out). `SalisbergWalkIn::getRoomBoard()` now uses the same definition: checked in and not checked out, whatever the dates, with the unused `$today` removed. The date window was dropped, not widened: a checked-in booking can only exist within or after its stay.
- **Bug in my test script, not the app:** the first admin run failed 17 steps because the script posted last names containing digits, which the Guest step correctly refuses. Its drafts (seven in all with earlier tests) were left active and were discarded through the Room board's Discard button; the board lists five at a time, so it took two passes. Room holds were released: no cart rows remain for discarded drafts.
- **Observed, not changed:** the Today panel's Departures list shows only guests due out today or earlier, so a guest who leaves a day early has no Check out button there; staff use the booking page. Worth a decision if early departures are common.
- Guides: What's New and CHANGELOG (Fixed). Staff Guide needed no change: the occupied definition is what its section 6 already says.
- **Not tested:** a real browser end to end this pass (the earlier headless screenshots covered the Room board layout and the date click); Hotel Manager; a booking with several rooms, with discounts or extra services, in tax-free mode, or for a guest with a Ghana-format phone beginning +233; sending of the confirmation email and SMS (no mail server or SMS provider here); the live server. Local test data left: eight new bookings (references starting with E, I, T, B, K, V, F) and guests named Flow Admin/Flow Staff.

### 2026-10-16 — Customer booking from the website, light and dark; dark-mode faults fixed

- **Owner:** test a customer booking from the site, in dark and light mode.
- **Method:** a small headless-Chrome driver over the DevTools protocol (outside the repository) acts as a visitor with real mouse events: homepage, the theme switch (a real click on the sun/moon), hotel and date picker (opening it, moving to next month, clicking two days), Search Rooms, Select Occupancy and Done, Book Now, the cart pop-up, Proceed to checkout, Proceed, the guest form (new account, signed in on save), Proceed, tick the terms, choose a payment method, "I confirm my booking", and the confirmation page. A scan run on every step lists text with contrast under 3.2:1 and large light backgrounds, so dark mode was judged by measurement as well as by screenshot.
- **Result, functional (both themes):** the journey completes. Bookings created: cash (light, then dark), Mobile Money (dark), bank transfer (dark twice, light twice). Each shows on the confirmation page with the right method, the booking reference as payment reference, the MoMo or bank details from Salisberg Pay settings, GH₵400 for two nights of Standard Room 1 plus GH₵16 tax (GH₵416; Deluxe GH₵624), state Awaiting payment, GH₵0 paid; each is listed under Bookings for the administrator; no JavaScript error on any page in either theme. The cart count falls back to 0 after the booking.
- **Dark-mode faults found, and the fix at each source (rule 11):**
  - `#login_form` and `#tc_cont` (checkout "Already have an account?" and terms) had `background-color: #FFFFFF` in `css/order-opc.css` and light text: now `var(--sb-surface)`.
  - `.label` (status label) and `.product-detail .product-name a` (room name) were `#333` in `css/order-confirmation.css`: now `var(--sb-ink)` and `var(--sb-heading)`.
  - `a.iframe` ("Read the Terms of Service") was `#333` in `css/global.css`: now `var(--sb-ink)`.
  - The room search placeholder was `#333` in `modules/wkroomsearchblock/views/css/wk-global-search.css` (four rules): now `var(--sb-muted)`.
  - `#order-opc .button-medium > span` forced white text in `css/order-opc.css`, so the gold Subscribe button kept white text: now `inherit`.
  - New token `--sb-on-gold` in `salisberg.css` (white in light, the deep green in dark) used by every gold-background button rule; previously white on the lighter dark-mode gold.
  - The dark rule that colours all spans in `#columns` and in the search panel also coloured spans inside buttons, which overrode the button's own text colour; both selectors now skip spans whose parent is a `button` or `.button`.
  - Amenity icons (`img/rf/*.jpg`, black on white) get `filter: invert(1); mix-blend-mode: screen` in dark mode, the same treatment as the logo.
  - Stylesheet link bumped to `?v=16` in `header.tpl`.
- **Re-scan after the fixes:** zero low-contrast text and zero unexpected light areas on search results, checkout, guest form, payment methods and confirmation in dark mode. In light mode the scan reports only light backgrounds (expected) and a few older borderline items (white on the light gold at 3.1:1, the pale "Clear Filter" text at 2.4:1, gold links at 3.1:1); those were not changed.
- **Seen, not changed:** payment option descriptions ("Pay at reception when you arrive") look tightly letter-spaced in both themes; the checkout still shows the demo hotel address "Demo City, Alabama" (owner's data to enter); the confirmation says "An email has been sent" although no mail server exists on this machine; the red "Please accept the Terms of Service" appears before the box has been touched.
- **Rate limiter confirmed in passing:** after about ten account creations from one address in an hour the sign-up silently stops (the guest form returns to itself). That is the limit of ten new accounts per address per hour set on 2026-10-06, not a fault; the counters were cleared in the container to continue testing.
- **Not tested:** phone width; the room page and the My account pages in this pass; a booking with extra services; a real mail or SMS; Safari and Firefox (the blend-mode trick on the icons and logo especially); the live server. Test data left locally: six website bookings (references FCZOQEIBI, LIDRJVENA, YLMNQQKJG, FFDYPAWYA, ROHJFPAGR, CYPVXSZGQ) and six guest accounts "Ama Daybreak" and "Ama Nightowl". The compile cache in the running container was cleared by hand after hot-copying files; an image rebuild starts without it.

### 2026-10-16 — Production error: SQLSTATE[42000] near 'LIMIT 1' when editing a booking's stay

- **Report (owner, from production):** `SQLSTATE[42000]: Syntax error or access violation: 1064 ... near 'LIMIT 1' at line 2`.
- **Cause:** `SalisbergWalkIn::getCartRoom()` passed a query ending in `ORDER BY id LIMIT 1` to `Db::getRow()`, which appends ` LIMIT 1` itself (`classes/db/Db.php`: `$sql = rtrim($sql, ...).' LIMIT 1'`, and `getValue()` goes through it), so MySQL received `LIMIT 1 LIMIT 1`. It is called only by `saveStay()`, i.e. **Edit stay details** (the screen reached from the Guest, Payment and Confirm steps, and the only way to change dates or room on a started booking). It dated from the first walk-in commit and was never exercised: every earlier test went straight through Guest, Payment and Confirm. This is the same family as the `FOR UPDATE LIMIT 1` fault fixed on 2026-10-15; that time only the two lock queries were looked at, not every `getRow()` or `getValue()` in the module.
- **Reproduced first, exactly:** against the running stack, start a booking, open Edit stay details, change dates and room, save: the same message, "near 'LIMIT 1' at line 2", and the stay unchanged.
- **Fix:** the `LIMIT 1` was removed from the query (`modules/salisbergfrontdesk/classes/SalisbergWalkIn.php`, `getCartRoom()`), with a comment saying why.
- **Searched the rest of the code for the same mistake:** every `LIMIT` in our modules, overrides and the controllers and classes edited here (about 30) was checked by hand against how its query is run. The others are subqueries inside a larger SELECT, `execute()` of an UPDATE, or `executeS()`; none is passed to `getRow()` or `getValue()`. The application log table was also searched for other SQL errors across the day's testing: only "Mailer Error" lines (no mail server here).
- **Verification after the fix (local, hot-copied, then image rebuilt):** Edit stay details as the administrator and as Hotel Staff, 7 of 7 steps each: start a booking, change dates only on the same room; save the guest, then go back and change to another room; an unknown room and 12 adults each give "That room is not available for the complete stay or cannot hold this many guests." (a plain message, no SQL); then payment and confirm. The first scenario also finished a booking and the database showed the edited room (Executive ER-101), 17 to 20 February 2027, 3 nights, GH₵1,404 (3 × 450 + 4% tax), the original room's hold on the first dates released, and no draft left active.
- **Guard added (rule 25: the existing smoke test, no new file):** `docker/smoke-test.sh` now scans the code in the image for a `->getRow(` or `->getValue(` call whose query text contains `LIMIT <number>` and fails with file and line. Shown working both ways: with the fix it reports "no getRow()/getValue() query carries its own LIMIT" (31 of 31); with the buggy file put back in the container it fails and names `modules/salisbergfrontdesk/classes/SalisbergWalkIn.php:531` (30 of 31, 1 failed). It checks only the text of the call, so a query built into a variable first, or a hand-written `getRow()` over a helper, is not covered.
- **Why none of the earlier checks saw it:** the page-load smoke test never opens a booking that is in progress, and the earlier end-to-end runs never used Edit stay details. Lesson recorded: after each flow works once, exercise its side screens (edit, back, resume, discard, error paths) before calling it done; the booking flow's remaining untested side paths are listed below.
- **Still not tested in the guided booking:** going back from Confirm to Payment and changing the method; changing the guest after the guest step; a price change between steps with "accept the new price" (the `accept_price` path); a booking started and then edited by two staff at once.
- **Deploy:** this is a code-only fix in `modules/salisbergfrontdesk`; no database step. Production needs a redeploy of the branch that carries it; after it, open New booking, start a booking and use Edit stay details once.
- Guides (rule 23): Staff Guide section 3, the Confirm step, now says how to change dates, guests or room (Edit, or Edit stay in the Stay summary) and what the system does; What's New and CHANGELOG (Fixed).

### 2026-10-16 — Confirm now, check in later (advance bookings and walk-ins who check in afterwards)

- **Owner (relaying the hotel owner):** people phone the front desk and pay in advance, then arrive later; someone may also walk in, book, and check in later. The Confirm step should offer two options (confirm and complete, or confirm and check in); after confirming, a check-in button should appear on the same page; if it is not clicked the guest simply has not checked in, and staff can later find the booking by name or reference and check them in.
- **Before:** a ready room arriving today offered only "Confirm & check in" (no way to confirm without checking in), the success page had no check-in action, and on the booking page the only way to check in was an "Edit" button opening a room-status pop-up.
- **Confirm step** (`walkin.tpl`): a ready room arriving today shows **Confirm & check in** and **Confirm booking — check in later**, grouped on the right, with a line saying when to choose the second. A future stay or a room that is not ready shows the single **Confirm booking** (the walk-in wording "Save booking — awaiting room" was dropped so the button has one name everywhere; the warning above it still explains the room).
- **Success page:** kicker "Booking confirmed" (was "Booking saved"). When the guest is not checked in: **Check in now** (a form posting `sb_check_in_now`) if the stay has started and the room is ready; otherwise a note saying why and where to check in later (a future stay: arrival date, Today on the Dashboard, or the booking under Bookings; a room not ready: the room and its state). `SalisbergWalkIn::success()` returns `can_check_in` and `check_in_note`; `checkInNow()` re-checks both, refuses a repeat or a future stay with a plain message, and calls the same `Salisbergfrontdesk::changeBookingStatus()` that Today and the confirm-and-check-in path use (rule 26: one check-in rule); `AdminSalisbergWalkInController` handles the button (needs the edit right on the page) and redirects back with the confirmation "The guest is checked in." so a refresh cannot repeat it.
- **Later check-in from the booking:** the booking page (`orders/helpers/view/view.tpl`, inherited admin template, edited in place per rule 11) shows a green **Check in** button on each room row that is allotted, not cancelled or refunded, and whose stay has started; it posts to the Today controller with `id_order_back`, and `AdminSalisbergTodayController` returns to that booking (`conf=4`) instead of the Dashboard. Staff reach it as the owner described: Bookings, filter by guest name or booking reference, open the booking, Check in. Today on the Dashboard (Arrivals) remains the quickest on the day. The existing Edit pop-up is unchanged.
- **Deliberately not done:** check-in from the booking page does not require the room to be Ready (as before, with the Today panel, the stock Edit pop-up and the staff guide telling staff not to give out a Dirty room); only the success page enforces readiness. If the hotel wants the booking page to refuse a dirty or occupied room too, add the same check to `changeBookingStatus()` so Today, the booking page and this page agree.
- Guides (rule 23): Staff Guide section 3 (the two confirm options, Check in now), section 6 (a new subsection for a guest who booked or paid in advance and arrives later; the From the booking itself step now says to click Check in); What's New and CHANGELOG (Changed).
- **Verification (local, hot-copied then image rebuilt; driven over HTTP in the order the pages make the requests, as administrator and as Hotel Staff, 16 of 16 checks each):** S1 a ready room today: both buttons on Confirm; Confirm booking leaves the room status Allotted (1) and the success page shows Check in now; clicking it makes it checked in (2) and the page reads Guest checked in with no button; a second click says "The guest is already checked in." S2 Confirm & check in checks in at once, no button. S3 a future stay: one Confirm button; the success page shows "Arriving <date>" and no button; a forced Check in now is refused and the status stays 1. S4 a today booking confirmed without check-in: its booking page shows Check in; posting it checks the guest in and returns to the booking page, where the button is gone. S5 a future booking's page has no Check in button. S6 a room not ready (occupied): Confirm shows only Confirm booking; the success page says the room is not ready; a forced Check in now is refused. Screenshots of the Confirm step and the success page reviewed (dark theme). The Bookings list filter was also tried: filtering by booking reference returned that booking, filtering by guest name returned the guests; the Staff Guide and What's New pages render with the new text. Re-run on the rebuilt image after the final build: 16 of 16 (administrator), smoke test 31 of 31.
- **Not tested:** the booking page button in a real browser click (the request it sends was posted directly); a booking with two rooms (each room has its own button; the success page's Check in now checks in every allotted room of the order); light theme screenshots of the two screens; the live server. Test data left locally: about 14 bookings for guests named Checkin Admin or Checkin Staff and Screen ShotKofi.

### 2026-10-16 — Booking tested end to end as administrator, Hotel Manager and Hotel Staff

- **Owner:** test booking as admin, manager and hotel staff. The administrator and Hotel Staff had been tested many times; the **Hotel Manager had never been run through a booking**, which was the real gap.
- **What was run, for each of the three roles, on the final image (scripts outside the repository; every request made in the order the pages make it):**
  1. Payment matrix, 30 checks: future stay / new guest / pay later; same day / new guest / full cash with change (check-in offered and taken); future / existing guest found by search / part payment by Mobile Money with a reference; same day / new guest with email / full bank transfer. The database is read afterwards for state, amount paid, payment rows and room status.
  2. Confirm now, check in later, 16 checks: the two Confirm options; Check in now; Confirm & check in; a future stay; checking in later from the booking page; a room that is not ready.
  3. Edit stay details, 7 checks.
- **Result: administrator 53 of 53, Hotel Staff 53 of 53, Hotel Manager 53 of 53.** For the manager the account's rights were first read from the database (view and edit on Room board, Guided booking, Today, Housekeeping, Bookings, Customers, Invoices, Booking Carts; Hotel Permissions ticked for the hotel): nothing needed granting.
- **After booking, as Hotel Manager:** the Dashboard Today panel offered Check out and a check-out set the booking to checked out and the room to Needs cleaning; the Housekeeping page loaded; the booking page opened with no permission error and showed the cash payment and GH₵208 paid; the booking was listed under Bookings and Invoices; its invoice PDF downloaded as a valid PDF (486 KB).
- **Test-script faults found along the way (not app faults):** the check-in script's setup for a not-ready room could not find a free room because earlier test bookings held today's rooms, and one regular expression lost its backslashes (`(d+)` instead of `(d+)`), which made the room list look empty; two post-booking checks hard-coded the GH₵2,600 total of a Luxury room and a PDF link pattern that does not match. Each was proved a script problem before being changed (the page showed GH₵208 and the PDF downloaded). Test rooms were freed by checking out or cancelling local test bookings in the database between runs.
- **Left unchanged:** the Today panel's Departures list shows only guests due out today or earlier, so an early leaver is checked out from the booking page (noted on 2026-10-16 earlier).
- **Not tested:** the Hotel Manager through a real browser; two people booking the same room at the same moment; the manager and staff on the website's own booking (they use the same public pages as any guest); the live server. The three test accounts' original passwords were restored (hashes compared). Local test data: many bookings for guests named Checkin, Flow, Stayedit or Screen.

### 2026-10-16 — Confirm & check in submits the selected action

- **Report:** clicking **Confirm & check in** left the employee on the Confirm step.
- **Reproduced in the local app before the fix, using a real Edge form click:** the submit handler disabled every submit button before the browser serialized the form, including the clicked button that carried `sb_finalize=checkin`. The POST body had no fields, the success view did not appear, and the page remained on Confirm.
- **Fix** (`modules/salisbergfrontdesk/views/js/walkin.js`): track the clicked submitter, preserve its `name` and `value` in a hidden input during submit, then disable the buttons as before to prevent duplicate submissions. The browser's `SubmitEvent.submitter` is used when available; the click-tracked button is the fallback.
- What's New (everyone) and `CHANGELOG.md` updated.
- **Verification (local, rebuilt image, real Edge browser):**
  - `node --check modules/salisbergfrontdesk/views/js/walkin.js` and `git diff --check` passed; the rebuilt app container is healthy.
  - **Confirm & check in:** completed a same-day booking through Room board, Guest, Payment and Confirm. The browser POST included `sb_finalize=checkin`; the success view said **Guest checked in** for booking `HRWFMUGBD` in room ER-102. **Bookings** listed both the guest and reference, and the booking detail showed checked-in status.
  - **Confirm booking — check in later:** completed another same-day booking using `sb_finalize=reserve`, then clicked **Check in now** on the success view. It changed to **Guest checked in** for `GBJEJVLTI` in ER-103, and the guest and reference both appeared in **Bookings**.
  - The two completed test bookings and their test guest records remain in the local database. No production data was changed.
- **Not tested:** Hotel Staff and Hotel Manager roles in the browser for this specific button fix; the live server.

### 2026-10-16 — Side menu: Bookings after Dashboard; the "Catalog" rename rule retired

- **Owner:** after Dashboard the menu should list Bookings before Rooms & Services; and since the hotel does not use the name Catalog any more, the Catalog rule can go.
- **Order, existing databases:** `orderMenus()` in `modules/salisbergguide/salisbergguide.php`, called from `ensureSetup()` after `renameMenuEntries()`. If Bookings (`AdminParentOrders`) sits after Rooms & Services (`AdminCatalog`) it moves Bookings into that position and shifts the sections in between down one, logs it, and records `SBGUIDE_MENU_ORDER = 1`. It runs **once**, so an order set later in Administration > Menus is kept (rule 22). `MODULES_VERSION=26` so it runs on the next deploy. The step reads the current positions and acts on them; it does not assume the stock numbers.
- **Order and name, fresh installs (rule 11, fix at the source):** `install/data/xml/tab.xml` lists Orders before Catalog (positions come from file order), and `install/langs/en/data/tab.xml` now names the Catalog section "Rooms &amp; Services". With that, the rename rule has nothing left to do on a fresh install.
- **Rule retired:** the entry `'AdminCatalog' => array('Catalog', 'Rooms & Services')` is gone from `$menuLabels`. Proof it is not needed any more: a search of the tree finds no other reference to that rename; the live and local databases already carry "Rooms & Services"; and a fresh install built from this tree (below) shows "Rooms & Services" in the right place with the rule absent. **Caveat:** a database that was never deployed since 2026-10-12 (for example an old staging copy) still has the section named Catalog and will not be renamed any more; rename it once in Administration > Menus, or restore the entry for one deploy. The other rename entries (Bookings, Hotel Details, Inbox and so on) are unchanged, and the changelog entries from 2026-10-12 that describe the old rule stay as history.
- **Verification:**
  - Local database, image rebuilt: log `Modules step v26 done`; top-level order Dashboard, Bookings, Rooms & Services, Customers; the log line "Bookings moved ahead of Rooms & Services" and the marker present. The rendered sidebar was read as each role: administrator and Hotel Manager and Hotel Staff all list Dashboard, Bookings, Rooms & Services, Customers, and so on in that order (the manager and staff see only the sections their profile allows).
  - **Fresh install** from this tree in a separate throwaway stack (project `sbfresh`, own volumes, removed afterwards; the main stack's volumes were not touched): installer log "Installation complete", then `Modules step v26 done`; the database had Dashboard 1, Bookings 2, Rooms & Services 3, Customers 4; **no** "moved" log line, so the installer data alone produced the order and the name.
  - The two installer files parse as XML and list Orders before Catalog; `php -l` and `bash -n` pass; `docker/smoke-test.sh` 31 of 31 on the final image.
- **Not tested:** other installer languages (only `install/langs/en` is used here and was changed; the other language folders still say Catalog, which does not matter for an English install); the Administration > Menus screen after the change; the live server, where the step acts on the next deploy.
- Guides: no change (they give menu paths, not the order). What's New (everyone) and CHANGELOG (Changed).

### 2026-10-16 — Booking details: direct room-status actions and customer printout

- **What:** Rooms Status on a booking now shows a clear status badge and the available per-room **Check in** or **Check out** action. Both forms use the existing protected Today controller actions, keeping the normal booking-status validation and the check-out housekeeping update. Browser printing now presents a branded booking reference, guest contact, room and stay, payment history, total and balance, while hiding back-office navigation, controls and private notes.
- **Why:** Staff should not need to find the status editor just to check a guest in or out, and the browser print command should produce a document suitable to give to a guest rather than a scaled back-office screen.
- **How:** The booking detail template contains the print-only guest/header/payment summary and per-room action forms. Print rules are in the existing back-office stylesheet. Edge print emulation exposed an empty KPI strip and a clipped stay date; the print stylesheet now hides the KPI containers and allocates explicit widths to the visible room-table columns.
- **Guide:** Staff Guide section 6 explains the badge and direct actions; What's New lists the room-status and print changes.
- **Verification (local, rebuilt PHP 8.3.35 / MySQL 8.0.46 stack):** Edge print preview shows the reference, guest name and phone, room status, full stay dates, payment and totals; the KPI strip, back-office navigation and private notes are hidden, the room table remains tabular without clipped dates, and no browser errors occurred. As administrator, used the direct Check in action on local booking 6 and confirmed its reference appears in the filtered Bookings results; used Check out on local booking 116 and confirmed its status changed and the room's housekeeping state became dirty. `docker/smoke-test.sh` passed 31 of 31; `git diff --check` passed.
- **Not tested:** printing from other browsers, multi-room bookings, live deployment, or printing a booking with no guest phone/email. Only local test bookings were changed.

### 2026-10-16 — Booking details: room status dropdown replaces pop-up

- **What:** the room-row **Edit** pop-up was replaced by an inline **Change room status** dropdown beside the room. Staff choose a status to apply it immediately; there is no separate save button or scrollable panel. Check-in and check-out use the booking dates and configured hotel times.
- **How:** the dropdown is native `<details>` markup in the booking detail template and is positioned beside its trigger by `overrides.css`. `orders.js` submits the selected status and its matching date. The existing status handler and its validation, including check-out housekeeping updates, remain the submission path. The obsolete modal endpoint, launch code and template were removed; direct Check in and Check out actions remain.
- **Guide:** Staff Guide section 6 describes changing a room status from the dropdown; What's New and `CHANGELOG.md` list the change.
- **Verification (local, rebuilt PHP 8.3.35 / MySQL 8.0.46 stack):** `docker/smoke-test.sh` passed 31 of 31. Loaded the authenticated booking detail page for local booking `FFBWLQQUU` (order 116): the response contained the inline dropdown and both button labels, with no old modal trigger or modal markup. `php -l` on the changed controller and `node --check` on `orders.js` passed; repository search found no modal endpoint, trigger or template references; `git diff --check` passed.
- **Not tested:** expanding the dropdown and changing a status in a real browser; the date field's visibility/prefill and submitting check-in or check-out through this dropdown; the live server. The existing direct status-action path was tested in the prior entry.

#### Follow-up: compact status options applied immediately, optional actual check-in time

- **What:** removed the form labels, visible date field and **Save room status** button from the dropdown. The menu displays status options and one optional **Set actual check-in time** checkbox, with enough height to keep the panel in one view without an internal scrollbar. Selecting a status submits it immediately. By default, check-in uses the planned hotel time; when the checkbox is ticked, an editable time input appears, prefilled with the current local time. Check-out continues to use its configured time.
- **Guide:** Staff Guide section 6 now says status changes apply immediately. What's New and `CHANGELOG.md` describe the simplified control.
- **Verification (local, rebuilt PHP 8.3.35 / MySQL 8.0.46 stack):** rendered the menu on an authenticated booking page: three status options, no Save button or visible time field, and a prefilled time input revealed only by the checkbox. The panel has a 200px minimum height, no maximum-height, and visible overflow (no internal scrolling). Through the existing protected booking form, checked in local booking `RVDZMYLMG` once using its planned 12:00 time and once using a custom 15:37 time; both exact timestamps were saved. Restored that test booking to Allotted. Also restored order 117 to its original Allotted status after an earlier local dropdown test. `docker/smoke-test.sh` passed 31 of 31; PHP lint, `node --check` and `git diff --check` passed.
- **Not tested:** visual appearance and checkbox interaction in an interactive browser; live deployment. The page was rendered and both planned/custom values were exercised via authenticated HTTP requests; the tests did not alter production data.

#### Follow-up: room status options expand in one view

- **What:** the status form expands in the room table's normal flow instead of floating over it. Opening it also switches that table to a fixed-width, wrapping layout and lets the wrapper overflow visibly. The Rooms Status section grows vertically to fit the options without creating a scroll bar.
- **Why:** the in-flow panel's minimum width made the room table exceed its wrapper, which introduced a horizontal scrollbar instead of simply extending the section.
- **Verification:** rebuilt and restarted the local image; `docker/smoke-test.sh` passed 31 of 31 on PHP 8.3.35 / MySQL 8.0.46 with no PHP warnings or errors. Confirmed CSS only changes the table layout while a room-status menu is open, and the form has no fixed height, minimum width or scroll overflow. `git diff --check` passed. Interactive browser visual verification and live deployment were not performed.

#### Follow-up: confirm room check-in and check-out

- **What:** the booking detail page asks for confirmation before applying Check in or Check out, whether staff use the direct room-row action or choose the status from the Change room status options. Cancelling leaves the form unsubmitted.
- **How:** the status buttons carry their translated confirmation prompt, and the existing `orders.js` click handler prevents the default form submission when the confirmation is declined. Other room-status choices continue to apply immediately.
- **Guide:** Staff Guide section 6 explains the Yes/No confirmation; What's New and `CHANGELOG.md` record the change.
- **Verification:** `node --check` on `js/admin/orders.js` and `git diff --check` passed. Browser interaction, server-side status results after Yes, and cancellation behavior after No were not exercised; no production data was changed.

### 2026-10-16 — Booking recycle bin, employee visibility and booking-detail refinements

- **Booking archive:** deleting a booking now requires a reason and archives it instead of removing its record. `SalisbergBookingArchive` records the employee and timestamp, reason, guest contact details, booking value and currency, associated hotels, and prior room-cancellation flags. Archiving removes the booking from active lists, availability and reports. The Recycle bin shows deletion records and currency-grouped count/value totals; Hotel Managers can view and restore records when permitted, while Hotel Staff can archive but cannot view or restore them. Restore is a token-protected POST, checks access to every hotel on the booking and refuses a room-date conflict. Customer order history also excludes archived bookings. Module setup is version 27.
- **Employee visibility and safety:** only SuperAdmins can see or modify SuperAdmin employee accounts. Direct edits, deletes and status changes are guarded, as are bulk operations; self-disable and disabling/deleting the last active SuperAdmin are refused. `Employee::getEmployeesByProfile()` remains unchanged because internal workflows use it.
- **Booking detail UX:** the top summary has only four figures—booking total, paid, due and room count—rather than secondary booking date/source/message/payment-status tiles. This avoids a long overflowing KPI row; the detailed information remains in the booking sections below. Top-bar account, theme and website controls align to the far right. Check-in/out controls sit beside room status and use a nearby Yes/No confirmation rather than a browser-wide prompt. Guides, What's New and `CHANGELOG.md` describe the staff-visible changes.
- **Verification (local):** rebuilt the PHP 8.3.35 / MySQL 8.0.46 image; module setup completed at v27. `docker/smoke-test.sh` passed 31 of 31 using Git Bash. A reversible archive/restore test verified rejection of an empty reason, Hotel Manager access, currency-grouped totals, removal from customer history, room-availability release and restoration of the original booking validity and room cancellation state. Final PHP/JavaScript syntax checks, `docker compose config` and `git diff --check` passed. In an authenticated browser, the booking page rendered exactly the four intended summary figures, with no document-level horizontal overflow at a 1425px viewport; the top-bar controls appeared on the far right. The check-out confirmation was opened and cancelled without changing the booking.
- **Not tested:** employee visibility and all direct/bulk-action protections through the back office as each role; clicking the room confirmation with a real pointer and submitting the destructive Yes action; a multi-hotel archive/restore against real multi-hotel data; live deployment. No production data was changed.

### 2026-10-16 — Change room status dropdown applied nothing for check-in and check-out

- **What was reported:** on a booking, choosing **Checked in** or **Checked out** from the **Change room status** dropdown did nothing. The advance-payment test booking stayed on its status no matter which option was clicked.
- **Cause:** the dropdown's Checked in and Checked out option buttons were given the `sb-confirm-room-status` class used by the direct **Check in** / **Check out** buttons. That class's click handler calls `preventDefault()` and then looks up a confirmation panel by the trigger's `aria-controls` attribute. The dropdown options had no `aria-controls` (and no panel), so the click was swallowed and the form never submitted. The only other dropdown option, the current status, is disabled, so the entire dropdown was effectively dead.
- **Fix:** in `admin/themes/default/template/controllers/orders/helpers/view/view.tpl`, the dropdown options no longer carry `sb-confirm-room-status` or the unused `data-confirm-message` attribute; they keep `sb-room-status-option` (which sets the hidden `status_date` before submit) and now submit immediately, as the dropdown was originally designed. The direct Check in / Check out buttons keep their working inline Yes/No confirmation. In `js/admin/orders.js`, the confirmation click handler now returns without touching the default action when a `.sb-confirm-room-status` trigger has no matching `aria-controls` panel, so this class can never silently swallow a click again.
- **Verification (local, rebuilt PHP 8.3.35 / MySQL 8.0.46 image):** the rendered booking page for the advance booking showed the Checked in option with class `sb-room-status-option` only and no `data-confirm-message`. Posting the dropdown check-in exactly as the option submits it (with `status_date` set from the option's `data-status-date`) changed the booking from Allotted to Checked in with the planned check-in time, and the page returned no error. The booking was restored to its original Allotted state afterwards so the reporter can click it themselves. `node --check` on `orders.js` and `git diff --check` passed; `docker/smoke-test.sh` passed 31 of 31. `CHANGELOG.md` and What's New updated.
- **Not tested:** the same change through a real browser click; check-out through the dropdown; the live server.

### 2026-10-16 — README refreshed to match the current product

- **What:** `README.md` brought up to date: the stack now reads PHP 8.3 with MySQL 8.0 (8.4 behind `MYSQL_VERSION`); the "What is in this repository" table now lists `modules/salisbergfrontdesk` (Room board, Today, housekeeping, guided booking, recycle bin) and `modules/salisbergreports`; payments are cash, Mobile Money and bank transfer; the roles table notes SuperAdmin concealment and the manager's recycle-bin access; the "Working on the code" bullet matches the current rule 11 wording; a stray duplicated "License and Credit" line was removed; and the licence section states the project is MIT licensed by Salisberg while preserving third-party attribution.
- **Why:** the README still described the 2026-10-07 state and an older stack and editing rule.
- **Verification:** `git diff --check` passed. Documentation only, so no build or smoke test was run; `README.md` is not served by the website (removed from the web root by the `Dockerfile`).

### 2026-10-19 — A room's own price; Extra Information shown on the room card

- **Owner:** a room type is one price, but several rooms sit under it (a Standard room with a fridge and a steam iron should cost 400, another Standard room 300). Add, in Manage Room Types, an option to set the price of a room: empty means the room type price, set means its own; the same for every room type. Also let a little description show on the card. Asked afterwards: could Extra Information carry the description? Yes, and it does.
- **How the engine prices a room (read before designing):** `HotelRoomTypeFeaturePricing::getRoomTypeTotalPrice()` adds up the room type's price per night (with date-based price rules) and then calls the hook `actionRoomTypeTotalPriceModifier` with the totals by reference and the room's id. The cart and the order creation pass that id, so a hook is the supported place to adjust a room's price without rewriting the booking engine.
- **Where it lives (rule 25):** `modules/salisbergfrontdesk` (it already owns the Room board, per-room housekeeping and the front-desk rules). New class `classes/SalisbergRoomRate.php`, new table `sb_room_rate` (one row per room that has an own price: `id_room`, `price`, `date_upd`), created by `ensureSetup()`; `MODULES_VERSION=28`.
- **The Own price column** is added to the Rooms tab of the room type editor through the hooks that tab already offers (`displayHotelRoomListTableHeaderColumn`, `displayHotelRoomListTableRowColumn`), so the vendor save code is untouched: the price is validated in `actionValidateRoomInformation` (empty, or a positive number of up to two decimals; anything else gives "The own price of room X is not valid…" and nothing is saved) and stored from the platform's own `actionObjectHotelRoomInformationAddAfter/UpdateAfter` hooks, matching a posted row to the saved room by id, or by room number for a room added in the same save. Emptying the box removes the row; deleting a room removes its row (`…DeleteAfter`). A save that does not come from that form (import, duplicate) leaves prices alone. Two vendor edits in `admin/themes/default/template/controllers/products/configuration.tpl`: the JavaScript that builds a row for "Add More Rooms" numbers the new box (`.replace(/__ROW__/g, lengthRooms)`), and, a fault found on the way, the Extra Information box of such a new row was named `[floor]` and so overwrote the floor and lost the extra information; it is now `[comment]`.
- **What the price means:** per night before tax, like the room type price. The hook adds, for every night, the difference between the room's price and the room type's regular price (read the way the engine reads it) with the same tax rate. So the room's price replaces the regular price, and special prices set for the room type on certain dates still apply on top, as the same difference. Bookings already made keep their stored price.
- **Where it applies, and where it deliberately does not:** only when a staff member is in context (`Context::getContext()->employee`), i.e. New booking at the front desk, where staff choose the room. The website sells a room type and allots the room itself, so a guest must pay the price they were shown whichever room they get; the website therefore stays at the room type price. This is a business choice, stated to the owner; the alternative (own prices on the website too) would need the allotment order and the displayed price to agree.
- **Extra Information on the card:** the existing per-room box (`htl_room_information.comment`, a plain text field used nowhere else) is shown in small italics on the Room board card (two lines, the full text on hover) and in the Selected room panel; the board's queries select it as `note`. A first version added a second "Short description" column and a `note` column in `sb_room_rate`; the owner pointed out Extra Information already exists, so both were removed (the table was never deployed outside the local database, where the column was dropped and the text moved into each room's Extra Information).
- **Board quotes:** a room with an own price is quoted on its own (`estimateQuote()` with the room id); the other rooms still share their room type's quote.
- Styles: `.sb-room-note` and `.sb-selected-note` in `frontdesk.css`; one min-width for the new box in `overrides.css`.
- Guides (rule 23): Admin Guide section 3 (the Rooms tab fields, a new subsection "Different prices for rooms of the same type" with the website rule); Staff Guide section 3 (what a card shows; "Continue bar" corrected to "Book now bar"); What's New and CHANGELOG (19 October).
- **Verification (local, image rebuilt, driven in a real Chrome and over HTTP):**
  - Rooms tab (administrator): the Own price column appears; typing a price and an Extra Information and saving gives "Successful update" and the values are stored and reload; an invalid price (abc, -5) is refused with the new message and nothing is saved; emptying both removes the row; a room added with "Add More Rooms" saves its price and extra information (the floor/comment fix); deleting a room (through the room object, not the screen) deletes its row. As Hotel Manager the same save works. As Hotel Staff a forged direct POST is refused ("do not have permission to edit this") and the price and extra information stay unchanged.
  - Room board, 2 nights, Standard Room 1 (room type 200 before tax, 4% tax): GR-101 GH₵208 per night; GR-102 own price 300 shows GH₵312; GR-104 own price 275 shows GH₵286; GR-106 own price 350 shows GH₵364; GR-103 with only an Extra Information stays GH₵208; the Extra Information shows on each card.
  - Bookings through New booking: GR-102 booked GH₵624 (rooms 600 excl, 624 incl; invoice 624), GR-101 GH₵416, GR-106 GH₵728, GR-103 GH₵416, GR-104 GH₵572; the total on the Confirm step matched the saved order and invoice every time.
  - **Website unaffected:** search results list Standard Room 1 at GH₵200; a website cart for 2 nights was allotted GR-106 (own price 350) and the checkout still read GH₵400 + GH₵16 tax = GH₵416.
  - Guides: Admin Guide, Staff Guide and What's New render with the new text for administrator, manager and staff (staff correctly have no Admin Guide).
- **Not tested:** a room type with a special date price rule combined with an own price (the difference rule is as described but was not exercised); two currencies; editing a room's own price while it has future bookings beyond confirming they keep their stored price (not re-checked after the change); the website's own date pricing for a room with an own price; the Rooms tab in a real browser at phone width; the live server, where the table and hooks are created by the next deploy (`MODULES_VERSION=28`). Local test data: Own prices on GR-102 (300), GR-104 (275) and GR-106 (350, a test room), five test bookings made at those prices, and the hand-set Extra Information texts.

### 2026-11-09 — Dashboard opens on today's work; charts stay and Day means today

**What**

- The coloured arrival and departure tiles at the top of the Dashboard are no longer drawn. They counted only stays dated today, so they disagreed with Today at the desk (10 of 11 live departures were overdue, still checked in). `dashguestcycle::hookDashboardTop()` returns nothing. The Operations Today panel, which is the same module's chart, is unchanged.
- Today at the desk lists fold. Arrivals is open when someone is still to check in. Departures is open for rooms still to check out, including Overdue; rooms already checked out today are a separate closed list. Unpaid is open when money is owed. Rooms in house starts closed. Clicking a count opens that list. The Check in and Check out buttons, and the controller that handles them, are unchanged.
- The Day, Month and Year charts stay on the Dashboard, under the desk. `override/controllers/admin/AdminDashboardController.php` sets the chart period back to Day when the saved range no longer includes today. Day-1, Month-1 and Year-1 are left alone, and a Month or Year range that still includes today is left alone. A line above the buttons says they change the charts only.

**Why**

- On the live Dashboard (9 November 2026) the tiles said 1 departure and 4 occupied rooms while the desk said 11 departures and 14 in house, and the charts were stuck on 5 September–5 October. Staff need the desk. The charts are still the place for occupancy and income, and Day is how they show today. The Staff Guide and Admin Guide already describe the Dashboard this way (staff sections 2 and 6, admin section 14).

**How**

- Display only. No booking, payment, status or permission change. The split between "still to check out" and "checked out today" is done in `assignTodayTemplate()` from the same rows `getToday()` already returns.

**Guides**

- Staff Guide sections 2 and 6. Admin Guide section 14. What's New, 9 November 2026.

**Verification**

- `php -l` on the three changed PHP files: no syntax errors. The five changed templates compile with the bundled Smarty once unknown tags such as `{l}` are stubbed.
- Not run in a browser. The local stack was not running, and this is not deployed: the live site is unchanged until the next deploy. Check-in and check-out were not exercised against a booking.

### 2026-11-09 — Phone layout, and add to the home screen

**What**

- The back office viewport no longer locks the page at 75% zoom (`initial-scale=0.75`, `maximum-scale=0.75`, `user-scalable=0` in both admin headers). It is `width=device-width, initial-scale=1`, so a phone under 480px wide gets the named menu the theme already has for that width, and pinch-zoom works. The website header uses the same viewport. On Android, `themes/hotel-reservation-theme/js/global.js` was rewriting that tag to forbid zoom; it now keeps scale 1 and still hides the address bar.
- Sign-in, contact and account panels use 18px of padding under 768px (`salisberg.css?v=17`). Today at the desk puts its counts two across and its links on one full-width row (`frontdesk.css?v=10`).
- A home-screen install. `sw.js` at the site root has an empty `fetch` listener and stores nothing. `pwa/manifest.webmanifest` opens the website. `pwa/desk-manifest.php` opens the back office only when `?dir=` is a real admin folder inside the web root (it must contain `init.php` and `themes/default`); otherwise the icon opens the website. Icons are `pwa/icon-180.png`, `icon-192.png` and `icon-512.png`, shipped in the image because `img/` is a volume. `modules/salisbergguide` loads `install-app.js`: Chrome and Edge get an Add to home screen button from `beforeinstallprompt`; iPhone gets the Share steps. Closing the note is remembered separately for the website and the desk.

**Why**

- Staff use the desk on a phone, and the locked zoom laid the page out as a narrow desktop: an icon rail instead of the named menu. Guests and staff both asked for a home-screen icon. A service worker that cached pages would show stale bookings, so this one does not cache.

**How**

- No booking, payment or permission change. The admin folder name is taken from `_PS_ADMIN_DIR_` in the two admin headers and checked again in PHP, so a guessed name does not publish a directory listing.

**Guides**

- Staff Guide section 1. Admin Guide section 1. What's New, 9 November 2026.

**Verification**

- `php -l` on `pwa/desk-manifest.php`. `node --check` on `sw.js` and `install-app.js`. The new guide lines compile with the bundled Smarty. The desk manifest query was rendered with `_PS_ADMIN_DIR_` set to `/var/www/html/back-engine` and produced `back-engine`. Icons are PNGs (green field, gold house).
- Not run in a browser, and not deployed. The local stack was not running. Chrome's install prompt and the iPhone Share sheet were not exercised. The live site is unchanged until the next deploy.

### 2026-11-09 — Shop menu pages and dashboard panels taken off the screen

**What**

- `$hiddenMenus` in `modules/salisbergguide/salisbergguide.php` now also sets `active = 0` on Groups, Titles, Shopping Carts, Catalog Price Rules, Stats, Themes, Search, Geolocation and States. `hideUnusedMenus()` logs each entry it actually hides. The controllers, tables and permissions stay. Cart Rules stays in the menu: the Admin Guide tells managers to create voucher codes there.
- `salisbergfrontdesk::replaceLegacyTodayWidget()` also unregisters `dashactivity` from `dashboardZoneOne` and `dashboardData`, and `dashproducts` from `dashboardZoneTwo` and `dashboardData`. Their order, customer and search hooks stay. `MODULES_VERSION=29` so the next deploy runs `ensureSetup()` for both modules.

**Why**

- These are shop screens and shop figures. Hiding the tab and unhooking the panel does not change check-in, prices or the website. Deleting the PHP would: the installer still creates the tabs, and a booking is still an order and a cart.

**Guides**

- Admin Guide sections 14 and 15. What's New, 9 November 2026.

**Verification**

- `php -l` on the two changed PHP files. The changed guide templates compile with the bundled Smarty.
- Not run against a database. The local stack was not running, so the menu flags and the hook rows were not updated here. That happens on the next deploy. Not deployed.

### 2026-11-09 — Search engine fields are made automatically

**What**

- **CMS pages (Preferences › CMS):** the form's "Meta title" is now labelled **Page title**, which is what it really is: the visible title and the footer link text. Meta description, Meta keywords and Friendly URL are shown to a SuperAdmin only (`AdminCmsController::renderForm()` removes them from `fields_form` for everyone else). `fillLinkRewrite()`, called at the top of `postProcess()` when the form is posted, fills an empty Friendly URL with the one the page already has, or else `Tools::str2url()` of the page title. `link_rewrite` is no longer marked required on the form (the model still requires it, so the validator is satisfied by the filled value).
- **Website description for a page** (`Meta::getCmsMetas()`, inherited, fixed at the source per rule 11): with no meta description entered, the first 155 characters of the page content (tags stripped, entities decoded, whitespace collapsed).
- **Hotels (`modules/hotelreservationsystem`):** `AdminAddHotelController::processSave()` no longer refuses an empty Friendly URL; it makes one from the hotel name (`$_POST` is filled before the per-language arrays are built). `renderForm()` passes `sb_show_seo` (`Employee::isSuperAdmin()`); `form.tpl` renders the SEO tab (now labelled "Search engines") for everyone but hides its tab link for a non-SuperAdmin, so the saved values are still posted back unchanged.
- Room types: the website already falls back to the room type name and short description (`Meta::getProductMetas()`); the room type SEO tab is part of the room type form pass (other agent), not changed here.

**Why**

- Owner: SEO URLs and marketing text should be picked automatically from the content people enter, not asked for by default.

**Design choices**

- **A link is made once.** It is derived only when empty, never rebuilt from a changed title, so addresses already shared or indexed keep working. An edit by a non-SuperAdmin posts the existing link (hotel, hidden tab) or has it restored from the saved page (CMS).
- **Render-time fallback, not stored text.** Descriptions are not copied into the database; the website computes them from the current content, so they cannot go stale. Keywords are not derived (search engines ignore them).
- **Not removed, hidden by role.** A SuperAdmin can still override every field; nothing in the model or the website changed for pages that already have values.

**Guides (rule 23):** Admin Guide section 3 (the Search engines tab bullet, SuperAdmin only) and section 9 (CMS row rewritten; new SuperAdmin row for the override fields; the SEO & URLs row now says fixed pages). What's New and CHANGELOG, 9 November.

**Verification (local, files copied into the running container, Apache reloaded)**

- As SuperAdmin: the CMS form shows description, keywords and Friendly URL; the hotel form shows the Search engines tab. As a throwaway Hotel Manager: the CMS form has none of those three inputs and reads "Page title"; the hotel tab link is hidden.
- As the manager: a hotel saved with the Friendly URL emptied (rest of the form posted as the page rendered it) succeeded and the slug is `salisberg-hotels`; a new CMS page "Pool Rules" saved with no SEO fields and got the link `pool-rules`; renaming it to "Pool and Spa Rules" kept `pool-rules`; once active, its website page showed the title "Pool and Spa Rules - Salisberg Hotels" and a description taken from the page content.
- Admin Guide and What's New load with the new text and no errors. `php -l` passes on the three PHP files.
- Test page 7 and the test employee were deleted. `docker/smoke-test.sh`: 30 of 31; the failure is a flood of `Undefined array key "id_order"` warnings from the Bookings list template, from work in progress in `AdminOrdersController`, not from these changes.

**Not tested**

- CMS categories, which still ask for meta fields and a Friendly URL (not used by this hotel); a second language; a hotel renamed by a manager (the slug-stays rule is by construction, not exercised); that Google shows the generated description; the live server.

### 2026-11-09 — Native PHP session upload-progress disabled

- **What:** `docker/php.ini` now sets `session.upload_progress.enabled = Off`. The Dockerfile already copies this file to `/usr/local/etc/php/conf.d/zz-salisberg.ini`, so it is baked into the image Coolify builds, not only the running container.
- **Why:** defence in depth. PHP's native session upload-progress writes upload state into `$_SESSION` keyed by a field name the client controls, a building block in session-poisoning chains. An outside security review recommended disabling it.
- **Safe here, checked first:** a tree-wide search found no use of `session.upload_progress` or `PHP_SESSION_UPLOAD_PROGRESS` anywhere; QloApps tracks uploads its own way (APC / its own AJAX). This setting does not touch `file_uploads`, so hotel/room images, booking documents and the Excel import keep working.
- **Verification (local):** copied the ini into the running container; `php -i` shows `session.upload_progress.enabled => Off => Off`; `file_uploads` is still `1`. Not re-tested: a full image rebuild and a real upload through the back office after it (the setting is documented by PHP to affect only the progress array, and `file_uploads` is unchanged).
- **Deploy:** takes effect on the next Coolify build; no data step, no `MODULES_VERSION` bump. No guide or What's New entry (nothing a back office user sees changes). CHANGELOG under Security.

### 2026-11-09 — Phone: the page-header "Menu" button no longer clips; light and dark checked

- **What:** on a narrow screen the back office page header collapses its action buttons into the stock responsive "Menu" dropdown button (`page_header_toolbar.tpl`). The stock button stacks a caret over its label and floats to the edge, so the label "Menu" was clipped. `admin/themes/default/css/overrides.css`, in the existing `@media (max-width: 767px)` block, now lays that button out as a compact inline caret-and-label pill (`.toolbar_btn.dropdown-toolbar`: `display:inline-flex; width:auto; white-space:nowrap`) and makes `.page-head .page-bar` a flex row so it sits inside the header's padding. CSS only, in the file meant for back office look (rule 25); no template or script change.
- **Why:** the owner asked to confirm the mobile view renders cleanly; the one nit found was this clipped button.
- **Verification (local, headless Chrome at 390px, signed in, measured and screenshotted in both themes):**
  - The button now measures right edge 380 of a 390 viewport (10px inside), width 98px, full text "Menu"; `document.documentElement.scrollWidth === innerWidth` (no sideways scroll) in light and dark.
  - Full mobile pass reviewed by screenshot and found clean, both themes: website home; back office sign-in, dashboard (Today at the desk, KPI tiles, collapsible lists, booking cards), the open named menu (the 9 Nov full-menu fix), the Bookings list (stacked cards), the Room board (date form, filter chips, selected-room panel, room-card grid). No JavaScript errors on any page.
- **Not a separate feature:** it is polish on the 9 Nov mobile work, so no guide change and no What's New line (nothing a user does moved or was renamed); CHANGELOG under Fixed. Takes effect as soon as `overrides.css` is served (it is not on a volume); returning staff may see the old rule until their browser refetches the stylesheet.
- **Not tested:** a real phone browser (Safari/Chrome on a device); widths between 768 and 991; the live server.

### 2026-11-09 — Booking alerts for staff (SMS + email), with a login phone prompt

**What the owner asked**

- When a back-office user who should receive booking notifications signs in without a phone number, show a prompt to add it. Clarified: this is for admins/staff who need alerts about bookings, not guests. Recipients are a chosen list, alerted on a new booking and on a payment. A later message: also send the alert by email when the mail server (SMTP) is set up, so email works alongside text.

**What was built (all in `modules/salisbergnotify`, which already owns mNotify texts — rule 25)**

- **Chosen recipients and their phones.** `SBNOTIFY_ALERT_EMPLOYEES` holds the chosen employee ids; each one's SMS number is `SBNOTIFY_EMP_PHONE_<id>` in Configuration (not on the vendor employee row, so no schema change). Helpers: `alertRecipients()`, `isRecipient()`, `setRecipients()`, `empPhone()`, `setEmpPhone()`.
- **Two channels, `notifyRecipients($subject, $message, $reference)`.** For each recipient: a text through `deliver()` when a key is saved and they have a phone, and an email through `emailRecipient()` (the module's new `mails/en/staff_alert.{html,txt}`) at the employee's own email when mail is enabled. `mailEnabled()` is `PS_MAIL_METHOD !== 3`; `canNotify()` is `hasKey() || mailEnabled()`. A failure on one channel or one person never stops the rest.
- **Events** (hooks registered in `ensureSetup()`): `actionValidateOrder` → "new booking REF for GUEST, N rooms, dates, total" (rooms/dates from `htl_booking_detail`); `actionOrderStatusPostUpdate` → "payment on REF, paid X of Y" when the new state is paid (full, `paid` flag) or `PS_OS_PARTIAL_PAYMENT_ACCEPTED` (part). salisbergfrontdesk still texts the guest on full payment separately; both are correct.
- **Login prompt.** `actionAdminControllerSetMedia` loads `views/js/phone-prompt.js` + `views/css/phone-prompt.css` only when the signed-in employee is a recipient, has no phone, and a key is saved (the prompt collects the SMS number, so it is pointless without a key; email needs no number). The modal asks for the number, posts it, and remembers a dismissal in `sessionStorage` so it does not nag every page; it reappears next session until answered. Colours come from the back office tokens, so it follows light and dark mode.
- **Save endpoint any employee can reach.** New hidden controller `AdminSalisbergPhone` (tab `id_parent = -1`, not in the menu) with `ajaxProcessSave()`: it saves only the current employee's number (never a posted id), validates with `Validate::isPhoneNumber`, and its `viewAccess()` returns true for any signed-in employee — so a Hotel Staff recipient, who cannot open Integrations, can still answer the prompt. The admin token the dispatcher requires is the CSRF guard (rule 21).
- **Integrations page** (SuperAdmin only) gains "Booking alerts for staff": a table of active employees with a tick box and a phone box (admin can set a number too), saved by `saveRecipients()`; and a line stating whether mail is set up (method and SMTP server) with a link to Advanced Parameters › E-mail.
- `MODULES_VERSION=30` so the next deploy registers the new hooks and the phone tab via `setup-modules.php`. No profile grant is needed for the phone tab (its controller grants itself).

**Guides (rule 23):** Admin Guide section 10 (new "Booking alerts for staff" subsection; SuperAdmin-relevant, shown in the admin guide); Staff Guide section 1 (what the sign-in prompt is); What's New (everyone: staff can be alerted; a SuperAdmin line on picking recipients). CHANGELOG under Added.

**Verification (local, running stack; files copied in, class-index cache cleared, Apache reloaded)**

- Hooks registered: `actionValidateOrder`, `actionOrderStatusPostUpdate`, `actionAdminControllerSetMedia`, `actionSalisbergSendSms`. Phone tab created hidden.
- With a key saved and the admin set as a recipient without a phone: the dashboard loaded the prompt (JsDef + script); the modal rendered correctly in a browser (dark mode screenshot reviewed). Posting a valid number returned `{"ok":true}` and stored it; an invalid number returned the error and stored nothing; after a number was saved the prompt no longer loaded.
- As a **Hotel Staff** recipient (profile 2): the prompt loaded and the save returned `{"ok":true}` and stored the number — the hidden endpoint works for a non-SuperAdmin.
- Integrations page: recipients table and the mail-status line render, HTTP 200, no errors; the earlier "Undefined array key email" warning (from `Employee::getEmployees()` not returning email) was fixed by a direct employee query and is gone.
- Sending: invoking both hooks on a real order ran clean; with a fake key and no real SMTP the SMS was refused by mNotify ("invalid api key") and the email failed at sendmail — both logged at severity 3, neither threw, as designed. On production a real key and SMTP will deliver.
- `php -l` on the three PHP files, `node --check` on the JS: clean. `docker/smoke-test.sh`: 31 of 31, no PHP errors.
- Local test config (fake key, recipients, phones) was cleared afterwards. The Hotel Staff test account password was set to a temporary value for the test and not restored (its original hash was unknown); it exists only in the local database.

**Owner must, for this to do anything on the live site**

1. Enter the mNotify API key (for texts) under Administration › Integrations, and/or set up the mail server (for emails) under Advanced Parameters › E-mail.
2. On the Integrations page, tick the staff who should receive alerts; optionally type their phone, or let them add it at the sign-in prompt.

**Not tested:** real SMS delivery through mNotify and real email through a live SMTP server; the modal in a real phone browser; a booking made end-to-end through the website while a recipient is configured (hooks were invoked directly on an existing order instead); the live server.

### 2026-11-19 — Dashboard redesign: shorter desk, brand-coloured charts, Demo mode for SuperAdmins

- **What:** the three recommendations of the Dashboard design critique. Inspiration came from the dataviz skill and from Dribbble front-desk dashboards such as Filllo "Hotel Management", Prostay and Stayntouch: neutral cards, one accent colour, status chips.
  - **Desk** (`modules/salisbergfrontdesk`: `salisbergfrontdesk.php`, `today.tpl`, `frontdesk.css?v=13`):
    - each tile has a sub-line (still to check in, N overdue in red, money owed) and a chevron;
    - departures are split into Leaving today and a folded Overdue list that shows days over;
    - empty lists are not shown;
    - Unpaid is sorted staying now first, then ended, then coming. It shows `UNPAID_SHOWN = 6` rows and a link to the full list in Invoices.
    - `stayRange()` makes the short dates and `paymentShort()` the short method names.
    - The stock `h3` panel rule is cancelled by a more specific selector, as on the Room board.
  - **Charts**, changed in the module files themselves (rule 11):
    - `dashtrends`: CSS, template and `$gfx_color`;
    - `dashperformance`: template and CSS;
    - `dashavailability`: colours;
    - `dashoccupancy`: classes `sb-occ-*`, and `sbOccupancyColours()` reads the tokens. Labels are Booked, Free and Blocked.
    - New `--sb-occ-*` tokens in `overrides.css`, with separate dark-mode values. The old pastel tile rule and the white-label rule were deleted (rule 27).
    - The palettes passed `validate_palette.js` in light and dark mode.
  - **Demo mode:** `AdminDashboardController` shows the switch, and accepts `setSimulationMode`, only for a SuperAdmin. `view.tpl` shows "Demo mode is on" while sample figures are displayed.
- **Guides (rule 23):** Staff Guide section 6 and Admin Guide section 14; What's New and CHANGELOG.
- **Verification (local, files copied into the running container):**
  - Dashboard height went from 7,852 px to 2,216 px.
  - Screenshots in light and dark, as administrator and as Hotel Staff. Staff see no Demo button, see the notice, and get Unpaid with 6 rows plus the link.
  - `docker/smoke-test.sh`: 31 of 31, no PHP errors.
- **Not tested:** that the Demo AJAX call is refused for staff (only the code was read); phone width of the new tiles; the live server.

### 2026-11-19 — Sentry error tracking

- **What:** Sentry project `salisberg` (platform PHP, team `365-42`, EU ingest) created at the owner's request. The Sentry PHP SDK 4.x is installed by the `Dockerfile` with Composer into `/usr/local/share/salisberg/sentry/` (outside the web root; Composer is removed afterwards, so the root still has no Composer step). `docker/sentry.php` initialises it; `docker/prepend.php` is now the `auto_prepend_file` and loads `sentry.php` then `ratelimit.php` (php.ini allows only one prepend file, so the existing file was not duplicated — rule 25).
- **Off by default:** nothing is sent unless `SENTRY_DSN` is set (compose, `.env.example`, `COOLIFY.md`). The DSN is not committed (rule 4); it is shown in Sentry under Project Settings › Client Keys. `send_default_pii` is false; notices and deprecations are excluded because the inherited code raises many; tracing is 0 unless `SENTRY_TRACES_SAMPLE_RATE` is set. If the SDK is missing or init fails, the request continues and one line is logged.
- **Limit:** PrestaShop catches its own `PrestaShopException`s and renders an error page, so those are not reported unless they reach PHP's uncaught handler or are PHP errors/fatals. Capturing them would need a `Sentry\captureException()` call in `PrestaShopException::displayMessage()`; not done yet.
- **Verification (local, image rebuilt):** SDK present in the image, prepend active, `SENTRY_DSN` passed to the container; a fatal error from a CLI script reached Sentry as issue SALISBERG-1. `php -r` does not run prepend files, so test with a script file. Not tested: a request through Apache, the live server, the backup service (it uses the same image but has no DSN set, so sends nothing).
- No guide or What's New entry: nothing a back office user sees changes.

### 2026-11-19 — Booking messages: short texts, longer emails

- **What:** one set of messages, written from one set of facts.
  - `Salisbergnotify::bookingFacts()` reads a booking once: guest, phone, real email, rooms, dates, nights, total, paid, balance and method. `detailsHtml()` and `detailsText()` turn label/value rows into the email table (the HTML version is escaped).
  - **Staff alerts** (`salisbergnotify`): `notifyRecipients()` takes the email rows as a fourth argument, so the text stays one line and the email gets the table. They now go out on a new booking, part payment, payment in full and **cancellation** (`PS_OS_CANCELED`). `staff_alert.html` and `.txt` gained `{heading}`, `{details_html}` and `{details_txt}`.
  - **Guest texts** (`salisbergfrontdesk`): booking made (a new `actionValidateOrder` hook, registered in `ensureSetup()`), part payment, payment in full, cancellation, check-in (reworded) and check-out.
  - **Guest emails:**
    - New: a thank-you email when the last room of the booking checks out (`notifyCheckOut()`, templates `mails/en/checkout.html` and `.txt`), sent through `emailGuest()`.
    - Unchanged: the platform's own booking confirmation, payment and cancellation emails, which already carry the invoice. They were not duplicated.
  - `deliver()` writes `GH₵` as `GHS`. `₵` is outside the GSM alphabet and would turn each text into 70-character parts, so every text would cost two or more SMS. All texts measured 85–122 characters on a real booking.
  - `MODULES_VERSION=31`, so the new hook is registered on deploy.
- **Guides (rule 23):** Admin Guide section 10, the table of what a guest is sent plus a paragraph on text versus email; What's New and CHANGELOG.
- **Verification (local):**
  - `php -l` passes on both modules, and `setup-modules.php` ran.
  - The frontdesk module is registered on `actionValidateOrder`.
  - `bookingFacts()` on booking NKDGFJVVP gave the correct rooms, dates, nights and amounts. Every text was built from it and measured.
  - A `<b>` in a value comes out as plain text in the email table.
- **Not tested:** real delivery of any text or email (no mNotify key and no mail server here); the check-out email rendered in a mail client; a cancellation run end to end; the live server.

### 2026-11-19 — Booking codes SB-YYMMDD-NN (cleanup plan, step 0)

- **What:** `Order::generateReference()` (`classes/order/Order.php`, inherited, changed at the source per rule 11) now returns `SB-` + the booking date as `ymd` + a two-digit day counter, for example `SB-261119-07`. It is the only place codes are made; `PaymentModule::validateOrder()` already loops until a code is unused.
  - **Counter:** table `qlo_sb_reference_counter` (`day`, `n`), raised with `INSERT … ON DUPLICATE KEY UPDATE n = LAST_INSERT_ID(n + 1)`, which is atomic, so two simultaneous bookings get different numbers. The 100th booking in a day becomes `-100`. If the table is missing (a fresh image before its schema step), the stock random code is used.
  - **Columns:** `qlo_orders.reference` and `qlo_order_payment.order_reference` were `varchar(9)`. The schema step in `docker/entrypoint.sh` (`SCHEMA_VERSION=2`) reads each width and widens it to 16 only when shorter, logging what it changed (rule 22). It also creates the counter table. `OrderPayment::$definition` size became 16, and `install/data/db_structure.sql` was updated for fresh installs.
  - **Existing bookings untouched** (owner decision): nothing rewrites old references; widening a varchar keeps its values.
  - **Checked for 9-letter assumptions:** `Validate::isReference` and the Contact form override's `id_order` pattern both accept hyphens; no code measures a reference's length.
- **Owner decisions:** format date + counter, prefix SB, two-digit year (chosen over `SB-1119-07`, which would repeat every year). Recorded in `CLEANUP-PLAN.md`.
- **Guides:** Staff Guide section 4 (what a code looks like); What's New and CHANGELOG.
- **Verification (local):**
  - The columns were widened and the counter table created.
  - Three generated codes were `SB-261119-01…03`; the counter was then cleared.
  - Two real bookings made through the guided-booking service (`SalisbergWalkIn`, as administrator) got `SB-261119-01` and `-02`; `Order::getByReference()` finds them.
  - A cash payment on `-02` stored `SB-261119-02` in `order_payment`.
  - All 145 earlier bookings still have their 9-letter codes.
  - `docker/smoke-test.sh`: 31 of 31.
- **Not tested:** a booking from the website checkout; the new code on the invoice PDF, in emails and texts; Excel-imported bookings; two bookings at exactly the same moment (atomicity is by construction, not exercised); the schema step through a rebuilt image (it was applied by hand in the running container); the live server.
- Local test data: two bookings for guest "Code Tester" about 200 days ahead.

### 2026-11-19 — Booking milestones

- **What:** congratulations at 50, 100, then every 100 up to 1,000, then every 500 (`SalisbergMilestones::steps()` and `next()`). Owner choices: these steps, everyone in the back office sees it, all three channels, and bookings count unless cancelled, refunded or in the recycle bin.
  - **Code:**
    - `modules/salisbergfrontdesk/classes/SalisbergMilestones.php`, in the module that owns the Dashboard desk (rule 25).
    - The count excludes `PS_OS_CANCELED`, `PS_OS_REFUND` and rows in `sb_deleted_booking`.
    - Reached milestones are stored in `SBFD_MILESTONES`, as JSON holding the number, the date and the booking that reached it.
    - Each person's closed banner is stored in `SBFD_MILESTONE_SEEN_<id_employee>`.
  - **First run:** every milestone already passed is recorded, with the booking that reached it, but announced nowhere. A site with hundreds of bookings is therefore not flooded with alerts; the newest one shows once as a banner.
  - **Announced from `hookActionValidateOrder`** through `salisbergnotify::notifyRecipients()`: a one-line text, plus an email with the milestone, the date, the booking and the next milestone.
  - **Dashboard:** a banner above Today at the desk (closed with a token-checked link), and a folded Milestones panel at the end of the desk showing the count, the next goal and the last five reached. Styled with the existing tokens; `frontdesk.css?v=14`.
  - Both entry points catch `Throwable` and only log, so a milestone fault can never stop a booking or the Dashboard.
- **Fault caught in testing:** the first version called `Tools::displayNumber()` with one argument. The fatal error was not caught by `catch (Exception)` and would have broken the booking that reached the next milestone. It now uses `number_format()` and catches `Throwable`.
- **Guides:** Staff Guide section 6 (banner and list); Admin Guide section 10 (milestones go to the alert recipients); What's New and CHANGELOG.
- **Verification (local):**
  - The steps up to 3,000 come out as intended.
  - The first run recorded 50 and 100 (146 counting bookings) and announced nothing.
  - With only 50 recorded, the announcement recorded 100 and the banner became unseen for the administrator.
  - Screenshots in light and dark show the banner and the panel; no PHP or JavaScript errors.
- **Not tested:** a real text or email (no key or SMTP here); the banner as Hotel Staff; closing the banner by click (the link is built and the token checked, but it was not clicked); the live server, where the first run records what has already been passed.

### 2026-11-19 — Rule 11 reworded; rule 31 and the simplify review added

- **Rule 11 (owner approval, 2026-11-19):** `override/` is no longer used. Changes to inherited classes and controllers are made in those files. The seven existing overrides will be folded in one at a time (`CLEANUP-PLAN.md`, O1–O7).
- **Rule 31 and `SIMPLIFY-REVIEW.md`:** every agent looks for ways to make the app simpler, writes them as proposals with evidence, benefits, risks and how to undo, and builds nothing until the owner approves.
- **First review (rota area 1, making a booking):** proposals P-1 to P-6 added, all Proposed, awaiting the owner. Evidence came from the guided-booking template and the website booking test of 2026-10-16.
- Documentation only; no code changed.

### 2026-11-19 — Simplify review P-1, P-2, P-5 built

Owner approval 2026-11-19 (`SIMPLIFY-REVIEW.md`).

- **P-5 (Book on room cards):** `room_board.tpl` adds a "Book" control under the price on every available card. `room-board.js` selects the card and then clicks the existing Book now button, so the same form and the same checks run.
- **P-2 (no tick for cash):** `SalisbergWalkIn::savePayment()` treats Cash with full or part payment as confirmed. `walkin.js` hides and disables the tick for Cash. Mobile Money and bank transfer still require the tick and a reference.
- **P-1 (Payment & confirm):**
  - The Confirm page is gone. The payment form carries the `sb_finalize` buttons, the price-changed warning and accept tick, and the future-stay and room-not-ready notices.
  - `AdminSalisbergWalkInController` saves the payment, re-reads the draft and finalizes in one request. A price change returns to step 3 with the warning.
  - Step 4 without a finished booking is shown as step 3.
  - The progress bar has two steps. The `sb_save_payment` handler, the review-section markup and its CSS were removed (rule 28; no other reference).
- **Mistake found and reverted:** adding `?v=` to `addCSS()` and `addJS()` made `Media::getMediaPath()` drop both files, because it checks `file_exists()` on the path including the query. The page lost its styles and scripts. Admin asset links stay unversioned.
- **Guides:** Staff Guide section 3 (Book on cards, the two steps, cash without a tick); What's New and CHANGELOG.
- **Verification (local, real headless browser, as administrator):**
  - The card Book button opened the guest step; the progress bar shows 2 steps.
  - On the payment step the tick was hidden for cash, and both Confirm buttons were present.
  - Confirm & check in created booking `SB-261119-03` (cash, GH₵312 paid, checked in), with no page or JavaScript errors.
  - Screenshots of the board and the payment step reviewed after the fix.
- **Not tested:** Mobile Money or bank transfer without the tick (should be refused); the price-changed path; part payment and pay later through the merged step; Hotel Staff and Hotel Manager; phone width.

### 2026-11-19 — Simplify review P-4 built

- **What:** the Payment & confirm step opens with two large figures, **Total** and **Still to pay**. The seven-line breakdown sits behind "Show breakdown" (`<details>`). This was the owner's choice from a preview of three options. Markup is in `walkin.tpl`; styles are the `.sb-pay-figures` and `.sb-pay-breakdown` rules in `frontdesk.css`, using the existing tokens. The two numbers stack below 480px.
- **Guides:** Staff Guide section 3; What's New and CHANGELOG.
- **Verification:** browser run through the booking flow; screenshot of the payment step reviewed in dark mode; no page or JavaScript errors.
- **Not tested:** light mode and phone width.

### 2026-11-19 — Side menu P-8; employee phone and alerts

- **P-8 side menu:**
  - In `admin/themes/default/css/admin-theme.css` (inherited, rule 11), three rules from the old dark sidebar were corrected: the current sub-page `color:#fff`, sub-pages `#BEBEBE` on `#363A41`, and the open section's sub-pages `#BEBEBE`. They now use `--sb-heading`, `--sb-ink` and `--sb-link`.
  - In `overrides.css` section 5, the current sub-page gets dark text, a pale chip and a 3px green bar; the other sub-pages use `--sb-ink`.
  - `side-menu.js` orders the sections into groups and adds a heading above each group the person can see: Front desk, Hotel, System, Help. Unknown sections stay at the end.
  - Headings are hidden below 768px (icon rail).
- **Employee phone and alerts (owner decisions 2026-11-19):**
  - `salisbergnotify` adds two fields to the employee form through `actionAdminEmployeesFormModifier`:
    - **Phone (for texts)**, for anyone who can open the form, including their own account;
    - **Receive booking alerts**, only when `canManageAlerts()` is true (SuperAdmin or Hotel Manager).
  - The values are saved from `actionObjectEmployeeAddAfter` and `actionObjectEmployeeUpdateAfter`, only when posted by `AdminEmployees`. An invalid phone is not saved and is logged.
  - They use the same stored values as the Integrations page and the sign-in prompt: `SBNOTIFY_EMP_PHONE_<id>` and `SBNOTIFY_ALERT_EMPLOYEES`.
  - The sign-in prompt now asks everyone with no phone saved, not only recipients with a key saved, and its wording depends on whether the person is a recipient.
  - `MODULES_VERSION=32` registers the hooks.
- **Guides:** Admin Guide section 1 (new step when adding an employee) and section 10 (the form as a second place to set alerts); Staff Guide section 1 (why the box appears); What's New; CHANGELOG.
- **Verification (local, browser as administrator):**
  - The Hotel Staff employee's form showed both fields. Saving "0244 555 123" with alerts Yes gave "Successful update", stored the phone and added employee 3 to the recipients.
  - The prompt appeared for the administrator, who has no phone.
  - The current sub-page measured in the theme's text colour.
  - `docker/smoke-test.sh`: 31 of 31.
- **Not tested:** the form as Hotel Manager and as Hotel Staff on their own account (the switch should be hidden for staff); switching alerts off from the form; the menu headings as staff and manager; production.

### 2026-11-19 — What's New pop-up

- **Owner decisions:** once per person per update; the latest date's items for their role; View more and their guide.
- **How:**
  - `salisbergguide::addNewsPopup()` runs from `hookActionAdminControllerSetMedia`, but not on AJAX requests or the What's New page.
  - It reads the newest date from the first `<section>` of `whats_new.tpl` (`latestNewsKey()`) and compares it with `SBGUIDE_NEWS_SEEN_<id_employee>`.
  - When they differ, it renders What's New through the existing `renderGuide()`, so role filtering is the page's own, and passes the first section's `<li>` items to `views/js/news-popup.js` with links: View more only if the person may open What's New; the Admin or Staff Guide by role.
  - It is marked seen as soon as it is shown, so no request is needed and it works for any profile. Opening What's New also marks it seen.
  - The script shows at most five items with "And N more", waits while the phone prompt is open, closes on ×, Escape or the backdrop, and returns focus to where it was.
  - Styles are in `views/css/news-popup.css`, using the back office tokens.
- **Fixed on the way:** today's What's New lines had been added under the 9 November heading. They now have their own 19 November section, which the pop-up depends on: one `<section>` per date.
- **Guides:** Staff Guide section 1; What's New (19 November); CHANGELOG.
- **Verification (local browser, administrator):** shown after sign-in with "19 November 2026", five items, "And 5 more", and the buttons "Open the Admin Guide" and "View more"; not shown again on reload; no JavaScript errors; screenshot reviewed in light mode.
- **Not tested:** as Hotel Staff and Hotel Manager (no View more for a profile without What's New); dark mode; phone width.

### 2026-11-19 — Occupancy panel rebuilt

- **Owner decisions:** tonight plus the occupancy rate; one bar in three parts; plain numbers, short dates and a shorter tooltip.
- **`modules/dashoccupancy` (inherited, changed in place, rule 11):**
  - **Data:** `hookDashboardData()` now computes, for the hotels the profile may see (`getProfileAccessedHotels`, or the hotel picked in the filter):
    - rooms with a guest checked in (`id_status` 2, not refunded or cancelled), as on Today at the desk;
    - rooms out of service (`id_status` ≠ active);
    - the rest as free;
    - the rate = booked room-nights inside the period ÷ (rooms in service × nights).
  - Dates are validated; the SQL uses `pSQL`, `(int)` and `getValue(..., false)` with no `LIMIT`.
  - Demo mode still gives random figures.
  - **Display:** the template is rewritten. The script draws the bar with the legend (swatch, name, number, %) and the donut code is removed.
- **Styles:** `overrides.css` replaces the old `.avail-pie-*` tile rules (used only here) and the donut size rule with `.sb-occ-*` rules, using the validated `--sb-occ-*` tokens.
- **Why:** the old figure counted any room with a booking anywhere in the period, so "26/28 (93%)" over a month sat next to "6 in house" on the desk. The numbers were zero-padded and the donut labels were clipped.
- **Verification (local):**
  - The hook returned 17 of 22 tonight, a 3% rate for 30 days and 18 booked nights. A separate per-night loop also counted 18. The low rate comes from local test guests never checked out.
  - Screenshots in light and dark reviewed; no PHP or JavaScript errors.
- **Not tested:** a specific hotel picked in the filter; production figures; phone width.

### 2026-11-19 — Integrations: cards and a test per recipient

- **Owner decisions:** a Test button on each row; SuperAdmin only; one card per integration.
- **`AdminSalisbergIntegrationsController`:**
  - Builds `sb_cards` for Text messages (mNotify key and sender), Booking alerts (people chosen, how many without a phone), Email (`PS_MAIL_METHOD`, linking to E-mail) and Error tracking (whether `SENTRY_DSN` is set). A new integration adds one entry there and its own panel.
  - `sendTestToEmployee()` handles `sb_test_one` before any save. It needs a saved key and a saved phone, sends through `deliver()`, and reports acceptance or refusal by name and number. The whole page is SuperAdmin-only, as before.
- **Template:** a card grid at the top, anchors on the panels, and a Test button on rows that are chosen, have a phone and have a key. Styles are in `overrides.css` (an Integrations block, using tokens).
- **Guides:** Admin Guide section 10; What's New (developer team); CHANGELOG.
- **Verification (local, browser):**
  - Four cards with correct statuses; 2 Test buttons.
  - With a fake key, Test showed "The test text to Admin Salisberg (0244000001) was not accepted…" and the log recorded mNotify's "invalid api key". The fake key was removed afterwards.
- **Not tested:** a real accepted text (needs the production key); dark mode; phone width.

### 2026-11-19 — First Sentry issues fixed: Recycle bin code, newsletter endpoint

- **SALISBERG-2** (`Undefined array key "reference"`, 10 events): `recycle_bin.tpl` prints `reference`, but `sb_deleted_booking` has no such column and `SalisbergBookingArchive::getEntries()` selected only the archive table. It now LEFT JOINs `orders` (the order row is kept when a booking is archived) for `o.reference`.
- **SALISBERG-3 to 6** (one request, same second): the `blocknewsletter` `subscription` front controller has `content_only` and no template, so a non-AJAX request or a wrong token fell through to a full page render (missing HOOK_HEADER, layout, template). `checkAccess()` (inherited, fixed in place per rule 11) now redirects such requests to the homepage; the footer form's AJAX call with the module token is unchanged.
- **Verification (local, files copied in, Apache reloaded):** `php -l` on both files; a direct GET and a POST with a wrong token both return 302 to the homepage; a real AJAX call with the token still returns its JSON reply; `getEntries()` runs without error; `docker/smoke-test.sh` 31 of 31.
- **Not tested:** the Recycle bin page with archived bookings in it (the local bin is empty); the live server. SALISBERG-1 is the Sentry setup test event.
- No guide or What's New change.

### 2026-11-19 — Dashboard "Rooms free" chart refined; newsletter action field

- **`modules/dashavailability`** (inherited, changed in place, rule 11):
  - `hookDashboardData()` validates the period (7, 14 or 30 nights; a valid date, else today) and asks `getAvailabilityLineChartData()` for `days - 1`, because that function returns one point more than asked. It also returns two `data_value` figures, `sb_avail_low` (tightest night and its date) and `sb_avail_avg` (average rooms free per night), escaped with `Tools::safeOutput()` because `data_value()` in `js/admin/dashboard.js` inserts them with `.html()`. Demo mode still gives random figures.
  - The template is a toolbar (date button, a 7/14/30 segmented control with `aria-pressed`) and a two-item summary. Before, the 115 px header used stock blue buttons; the 15 and 30 buttons had `data-days="15)"`; the date showed as `2026-11-19`. The date label is built in PHP (`date('j M Y')`), not with Smarty `date_format`, which relies on the deprecated `strftime`.
  - The script colours the series from `--sb-occ-free`, the "free" colour of the Occupancy panel, and draws it as a light area. Dates read "Thu 19 Nov", and there is about one date label per 90 px of chart, at most 7. The bold min/max labels nvd3 repeats on the y axis are hidden. The date picker no longer resets the start to the dashboard's own period start.
  - Styles are in the module's stylesheet and use the tokens. The two `.avail-bar-*` rules in `overrides.css` section 8 were deleted (rule 27).
- **`modules/blocknewsletter/blocknewsletter.php`:** `newsletterRegistration()` reads `newsletter_action` through `Tools::getValue()`, so a request without it is treated as a sign-up instead of raising "Undefined array key" (Sentry SALISBERG-7, 8, caused by the local test of the previous entry).
- **Sentry:** SALISBERG-1 to 8 marked resolved, each with a comment naming its fix.
- **Guides:** What's New 19 November (manager and developer team). No guide section describes this chart.
- **Verification (local, headless Chrome, signed in as administrator):** screenshots reviewed at 1440 wide in light and dark and at 390 wide. 7 nights gives 7 points; the summary reads "16 rooms free on Thu 19 Nov" and "19.9 rooms a night"; clicking 30 nights redraws with five date labels; no JavaScript errors; `php -l` and `node --check` pass.
- **Not tested:** a specific hotel picked in the dashboard filter; Hotel Manager; the date picker by click; the live server.

### 2026-11-20 — Rule 14 and the licence section reworded

- **Owner decision:** the owner removed the PrestaShop AFL header from `modules/blocknewsletter/blocknewsletter.php` (commit `4477e6af`). Old headers in inherited platform files may be removed and must not be added back.
- **Changed:** rule 14, and two lines in the licence section. Bundled third-party libraries keep their licence files, notices and copyright attribution.
- Change-log entries from before this date that mention rule 14 are history and were left as written. Documentation only.

### 2026-11-20 — Staff desk menu P-17; System group P-9; What's New tag fixed

- **Owner:** approved P-17 and P-9 (`SIMPLIFY-REVIEW.md`) after reviewing drawings of both.
- **P-17 (Hotel Staff menu):**
  - `salisbergguide::deskOnlyStaffMenu()`, run once from `ensureSetup()` (marker `SBGUIDE_STAFF_DESK_MENU`, rule 22):
    - moves `AdminOrderRefundRequests` from Manage Hotel to the end of Bookings, only while its parent is still Manage Hotel;
    - sets view, add, edit and delete to 0 for Hotel Staff on `AdminHotelReservationSystemManagement`, `AdminAddresses` and `AdminOrderMessage`, logging what it changed.
  - The same three were removed from `$staffAccess` for new installs.
  - `isDeskOnly()` assigns `sb_desk_only` in `hookActionAdminControllerSetMedia`. `admin/themes/default/template/header.tpl` (inherited, rule 11) leaves out the new-customer icon and Quick Access when it is set.
  - The bookings bell stays. The proposal called it a cart icon, but it lists new bookings, which the desk uses.
  - `today.tpl`: "Open room board" is now "New booking".
- **P-9 (System group):**
  - `side-menu.js` `foldSystem()` turns the System heading into a button. It folds the five technical sections and opens itself while one of their pages is current.
  - It is built in the menu script, not by moving tabs: the sidebar has two levels, so nesting sections would hide their pages, and moving them would change permissions.
  - Managers see only their own sections inside System. Staff, who have none, get no heading.
  - Below 768px the heading is hidden by the existing rule, so folding is applied above that width only (`overrides.css` section 5).
- **Fault fixed (found while editing):** the 19 November Occupancy line in `whats_new.tpl` had `{if}` without `{/if}`. The page and the sign-in pop-up could not compile, which is why production showed no pop-up on 20 November.
- `MODULES_VERSION=33`.
- **Guides:** Staff Guide menu table and section on refunds (Bookings › Refund requests); Admin Guide section 15 and its refund path; What's New 20 November; CHANGELOG.
- **Verification (local, files copied in, `setup-modules.php` run):**
  - Log: "Refund requests moved under Bookings" and "Hotel Staff menu trimmed: AdminHotelReservationSystemManagement, AdminAddresses". Booking messages was already off locally.
  - As administrator: System folded with no sections showing, and the five appeared after a click; New booking on the desk (screenshot reviewed).
  - As the Hotel Staff test account: the menu is Dashboard, Bookings, Customers, Rooms & Services and Guides; no System heading, no Quick Access, no customer icon; the bookings bell is still there.
  - What's New, Staff Guide and Admin Guide pages render for both roles, and the pop-up shows 20 November.
  - `docker/smoke-test.sh` 31 of 31.
- **Not tested:** Hotel Manager; phone width; the live server.
- The local Hotel Staff test account now has a temporary local password.

### 2026-11-20 — Income kept from Hotel Staff P-16

- **Owner:** approved P-16 (2026-11-19): staff keep Reports, but only the daily Arrivals and departures sheet.
- **One rule:** `Salisbergguide::seesIncome()` returns false only for the Hotel Staff profile, and true for SuperAdmins, Hotel Managers and any other profile. Other modules call it behind `class_exists('Salisbergguide')`, so they still work if the module is missing.
- **Where it applies** (all inherited files, changed in place per rule 11, except our own modules):
  - `dashtrends` and `dashperformance`: `hookDashboardZoneTwo()` returns nothing and `hookDashboardData()` returns no data, so the figures are not sent to the browser either.
  - `AdminOrdersController::renderKpis()`: no Total Sales tile on the list. Total Due Amount stays, because it is desk work.
  - `salisbergreports::getReports()` offers staff only `arrivals`. `buildReport()` and the controller's `getChoice()` now fall back to the first report offered, so `sb_report=income` in the address shows the arrivals sheet.
  - `salisbergreports::grantStaffSheet()` gives Hotel Staff view on Reports once (`SBREPORTS_STAFF_SHEET`). Taking it away later in Permissions is kept.
- `MODULES_VERSION=34`.
- **Guides:** Staff Guide menu table (Bookings › Reports); Admin Guide section 14 (what staff do and do not see); What's New 20 November for both roles; CHANGELOG.
- **Verification (local, files copied in, setup run):**
  - As Hotel Staff: no sales or performance panels on the Dashboard, while Occupancy and the desk are present; no Total Sales on Bookings, while Total Due is present; Reports offers only Arrivals and departures; `sb_report=income&sb_period=month` did not show Income by day.
  - As administrator: everything is present, as before.
  - `php -l` on the six PHP files.
- **Found while testing:** after many test sign-ins the local back-office limit (10 in 10 minutes) refused the staff account. The test cleared `/tmp/sb-ratelimit` in the container. This is not an app fault.
- **Not tested:** Hotel Manager; the PDF download as staff; the live server.

### 2026-11-20 — Lists in pages P-13

- **Invoices** (`AdminInvoicesController::renderInvoiceList()`, our own list in an inherited controller):
  - the 200-row cap (`LIST_LIMIT`) is replaced by pages of 20, or 50 (`$perPageChoices`), chosen with `sb_per` and `sb_page`. Both are validated: a page past the end shows the last page, and an unknown size falls back to 20.
  - The four totals come from a separate aggregate query over the same filtered set, so they cover every matching invoice and not only the page shown.
  - Pager links keep the filters. The pager is in `invoices/list.tpl`, styled by `.sb-pager` at the end of `overrides.css`.
- **Every stock list:** `AdminController::$_default_pagination` changed from 50 to 20 (inherited, rule 11). The list's own page-size menu still offers 20, 50, 100, 300 and 1000. A size someone already picked is kept in their cookie.
- **Guides:** Staff Guide list tip; What's New 20 November; CHANGELOG.
- **Owner choices recorded the same day:** P-11 keeps one quiet desk line on Bookings; P-15 changes new and edited names only.
- **Verification (local, files copied in):**
  - Invoices: page 1 shows rows 1–20 of 148 and page 2 shows 21–40. `sb_page=999` shows 141–148. `sb_per=50` gives 50 rows and `sb_per=7` gives 20. Filtering Paid and going to page 2 shows 21–38 of 38.
  - The totals (148, GH₵206,334.00 billed, GH₵98,930.00 paid) match a direct database sum.
  - Bookings, Customers and Logs show 20 rows each with the stock pager, with no errors. Logs went from about 7,400px to 2,100px tall.
- **Not tested:** phone width; the Hotel Staff and Hotel Manager roles on Invoices; the live server.

### 2026-11-20 — Side menu icons

- **Owner:** "modernise the sidebar icons too".
- **What changed:**
  - In `overrides.css` (beside the section icon rules), each top-level section listed there gets a `--sb-ico` SVG data URI: a line icon, stroke 1.8. The shapes follow Lucide (ISC licence, credited in the comment).
  - One rule draws it as a mask filled with `currentColor` on the section's existing `<i>`, so the muted, hover, active-pill and dark-mode colours apply unchanged.
  - A section not in the list (a future module) keeps its font icon.
  - The old Guides glyph rule was replaced, not overridden (rule 27).
- CSS only: no template, script or permission change.
- **Guides:** none needed, since nothing moved or was renamed. What's New (everyone) and CHANGELOG updated.
- **Verification (local, screenshots at 1440 in light and dark, System opened):** all 12 sections show their new icon, aligned with the labels; the active pill shows its icon in the pill's text colour.
- **Not tested:** the phone icon rail below 768px; Safari, where `-webkit-mask` is supplied too; the live server.

### 2026-11-20 — Lists, names, phone cards and Dashboard: P-7, P-10, P-11, P-12, P-14, P-15

- **Owner:** approved all of these after reviewing the drawings. Choices: P-11 keeps one quiet desk line; P-15 changes new and edited names only.
- **P-11, figure strips:**
  - `salisbergfrontdesk::splitToday()` is now the one place that splits today's lists. `assignTodayTemplate()` uses it, and so does the new `deskFigures()`.
  - `AdminOrdersController::renderKpis()`: the list branch shows four HelperKpi tiles (in house, still to arrive, leaving today with overdue, owed), filled from `deskFigures()`. No AJAX source. They link to the Dashboard and use the neutral `sb-desk` style. The shop tiles (sales, abandoned carts, conversion, averages) are deleted.
  - The `renderKpis()` methods of `AdminCustomersController`, `AdminProductsController`, `AdminCartsController` and `AdminTranslationsController` are deleted (rule 28). With no tiles, the base class draws no strip. The single-booking tiles on a booking page are unchanged.
- **P-12, Bookings list cells:** new callbacks `printListDay` (12 Nov 2026), `printListStatus` (a `.sb-state` chip with a dot: green, amber while money is owed, red for cancelled, refunded or payment error) and `printListDue` (red when owed, a dash when nothing is). The colour bar and `badge_success` pills were removed. Filters and sorting are unchanged.
- **P-10, Customers columns:**
  - New columns: `guest` (`CONCAT` of the names, filterable), `last_stay` (latest `htl_booking_detail.date_to`) and `owed` (unpaid balance, leaving out cancelled, refunded, failed and recycle-bin bookings). Each is a subquery, so the existing join is not multiplied.
  - The six columns lead the list. Every other column is marked `optional` and hidden by default, and can be switched on under Available Fields.
  - Email uses `printListEmail`: "No email" for `Customer::hasNoEmail()` addresses.
- **P-15, names:**
  - `Customer::tidyName()` capitalises names typed all lower or all upper case; a mixed-case name is kept as typed. `Customer::isTitleOnly()` matches a name that is only a title (Mr, Mrs, Ms, Miss, Mx, Dr, Prof, Sir, Madam, Rev).
  - `Customer::add()` tidies the names. `Customer::update()` tidies a name only when it differs from the saved one.
  - A title-only name is refused in the guided booking (`SalisbergWalkIn`), the order form's find-or-create guest (`override/.../AdminCustomersController.php`) and the admin customer form (`processSave`).
- **P-14, phone cards:**
  - `list_header.tpl` uses the wrapper `sb-cards` in place of `table-responsive-row` when `sb_phone_cards` is set; `AdminOrders` and `AdminCustomers` set it in `renderList()`.
  - `list_content.tpl` gives every cell the class `sb-col-<column>`, and the actions cell `sb-row-actions`. The Invoices template has the same classes and the wrapper.
  - CSS at the end of `overrides.css`, below 768px, turns each row into a flex card:
    - first line: who, and what is owed;
    - then the reference, room and dates;
    - then the status.
    The selection box and the actions cell (with Delete) are left off, and Invoices keep their PDF button. From 768px it is the normal table, scrolling sideways when needed.
  - Lists that do not opt in keep the stock stacked rows.
- **P-7, Dashboard:**
  - The Demo mode header button is replaced by `#sb-demo-toggle` beside the hotel filter, shown only when `sb_can_demo` is set (SuperAdmin). The server check in `ajaxProcessSetSimulationMode()` is unchanged. `dashboard.js` toggles the setting, then reloads so the notice and the charts agree.
  - The chart hint is one line.
  - `today.tpl` gives Overdue `sb-desk-act-bad` and Unpaid, when it has rows, `sb-desk-act-warn`: a 4px left edge, scoped above the stock panel border.
- **Guides:** Staff Guide menu table (Customers), the guest step (names) and the list tip (phone cards); Admin Guide section 14 (Dashboard); What's New 20 November; CHANGELOG.
- **Verification (local, files copied in):**
  - Bookings desk line: 18 in house, 1 to arrive, 0 leaving with 14 overdue, GH₵57,978 owed, the same as Today at the desk. Customers and Room types have no strip.
  - Bookings list: chips, plain totals and red Due checked by screenshot.
  - Customers: six columns, "No email", Owed in red; filtering Name=Flow works; no errors.
  - Names: a container script checked tidyName and isTitleOnly on 11 samples. A guest added as "ama" / "serwaa test" was saved as "Ama Serwaa Test". An update that did not touch the name left a lower-case name alone; editing the first name tidied it. The test guest was deleted.
  - Phone, 390px wide: page height went from 10,086 to 2,986px on Bookings, from 7,864 to 2,277px on Customers and from 14,476 to 6,907px on Invoices; screenshots reviewed.
  - Dashboard: the header button is gone. The toolbar button switched Demo on (the notice appeared) and off again. Both edges measure 4px.
- **Not tested:** Hotel Staff and Hotel Manager on these lists; filters in the phone layout (thead hidden; the stock Filters panel remains); dark mode on the phone cards; the website registration page (it uses `Customer::add`, so names are tidied, but a title-only name is not refused there); the live server.
- **Seen, not changed:** in dark mode the "Filters:" bar on a filtered list is still white.

### 2026-11-20 — Links and code typed by guests: production checked, one shared rule

- **Owner question:** some people tried to book with links and commands. What happens to that?
- **Production check (read only):**
  - Signed in as the agent account and loaded Customers (122), Addresses (9), Inbox (all) and Bookings (145) in full.
  - Scanned each row for web addresses, HTML or script tags, template syntax, SQL fragments (`UNION SELECT`, `DROP TABLE`, `OR 1=1`) and shell commands.
  - Result: none stored. The only matches were ordinary email addresses (gmail.com, cua.org.gh).
  - Refused attempts leave no record, because they are turned away before anything is saved.
- **Why nothing typed can run:**
  - SQL values go through `pSQL()` or casts; the Contact form was tested with injection payloads on 2026-10-13.
  - Output is escaped.
  - Inbox shows links as plain text.
  - The request limiter caps form posts.
- **Gap closed:** web addresses could still be saved in a guest name (`www.x.com`, `bit.ly`), an address, a city, or the message added at checkout.
  - `Validate::containsLink()` is new: the Contact form's detector, moved out of `override/controllers/front/ContactController.php`. The override now calls it, and its private copy is deleted (rule 26).
  - `Validate::isName()`, `isAddress()` and `isCityName()` refuse a value containing a link.
  - `ParentOrderController::_updateMessage()` refuses a checkout message containing one, with a plain message.
  - Email addresses are allowed (the detector ignores `name@host`).
- **Not a vendor security backport**, so it is not in `PATCHES.md`.
- **Guides:** Admin Guide section 9 table (new row); What's New 20 November (manager); CHANGELOG under Security.
- **Verification (local):**
  - Sample values: real names, "P.O. Box 4501, Accra", "Plot 7, Spintex Rd.", Accra and an email in a message all pass. `www.evil.com`, `bit.ly`, `freecash.xyz`, `http://`, `javascript:`, "visit www.scam.com", "scam.ru", "site.com" as a city and "cheap-hotels.com" in a message are all refused.
  - All 290 saved customer, address and employee name values pass the new rule. The production scan above found no links either, so no existing record is blocked from being edited.
- **Not tested:** a full website checkout carrying a message with a link (the check is in the shared save function); the live server.

### 2026-11-20 — Modules and Positions pages redesigned; category filter fixed

- **Owner:** approved both redesigns from the previews; for Modules, unused modules in a folded "Not used" group (the default offered).
- **Manage Modules:**
  - `controllers/admin/AdminModulesController.php` (inherited, rule 11): in `initContent()` modules are grouped (`sb_group`: `own` for `salisberg*`, `unused` for not installed or bank wire, cheque, PayPal and the channel manager, else `main`) and listed in that order; each gets `sb_initials` for its tile; `sb_nb_unused` is assigned. `renderKpis()` and the `kpis` template variable were deleted (rule 28: only `page.tpl` used them; the `AdminStats` KPI endpoints are left).
  - `page.tpl` rewritten: one summary line (on, off, not installed, updates), alerts only when there are some, category chips and a search box above the list. Removed: the Alert and Updates tabs, the figure strip and the vendor addons banner (and its now-unused hide selector in `overrides.css`). The element ids the stock script uses (`moduleQuicksearch`, `filter_all`, `categoryModuleFilterLink`) are kept.
  - `list.tpl`: group heading rows; letter tile instead of the logo; On, Off or Not installed chip (`.sb-state`); version and category on the name line; "Not used" rows start hidden and open with a button. They stay in the first `tbody`, so the search still finds them.
  - `admin-theme.css`: the green and grey bar of `td.module_active` / `module_inactive` made transparent.
  - **Fault found and fixed:** `isModuleFiltered()` read `PS_SHOW_CAT_MODULES_<employee>` into `$filter_categories` but tested an empty `$category_filtered`, so a category chip never filtered. It now fills `$category_filtered` from the saved value.
- **Positions:**
  - `AdminModulesPositionsController::initContent()` adds `sb_label` per hook: a plain name for 42 hooks this hotel uses, else the stored hook title when it differs from the name, else nothing.
  - `list_modules.tpl`: each hook header is a button (plain name, technical name, count); module lists start folded; a drag handle beside the position number (the arrows stay, for keyboard users); a letter tile instead of the logo; the description shows as the button's tooltip.
  - `js/admin/modules-position.js` (inherited): the hook search also matches the plain name; hooks found by a search or the module filter are opened, and folded again when the filter is cleared; a link to `#hookName` (used after moving a module) opens that hook.
  - `admin-theme.css`: the hook panel, header, name, list item, highlight, select and position rules use the tokens instead of the bright blue and grey bevels.
- **Guides:** Admin Guide section 15 (SuperAdmin part); What's New 20 November (SuperAdmin); CHANGELOG.
- **Verification (local, headless Chrome, administrator, dark mode):**
  - Modules: summary "58 modules · 53 on · 5 off · 0 not installed · all up to date"; Salisberg group first; 55 rows shown, 3 folded; opening Not used shows 58; searching "bank" finds 2; the Payments and Gateways chip shows 3 (it showed all 58 before the filter fix); no script errors. Screenshot reviewed.
  - Positions: 39 hooks shown folded, page 2,112px; a real click opens one; searching "footer" shows and opens the 7 footer hooks; with non-positionable hooks ticked, "booking status" finds "When a booking status changes"; clearing folds them again; no script errors. Screenshot reviewed.
- **Not tested:** dragging a module to a new position after the change (the sortable code is unchanged); installing or uninstalling from the new rows; light mode screenshots; phone width; the live server.

### 2026-11-20 — Test accounts hidden; title-only names on the website; dark Filters bar; PayPal module removed

- **Test accounts (owner request):**
  - `classes/Employee.php`: `getTestAccountIds()`, `isTestAccount()`, `setTestAccount()` (ids stored as JSON in `SB_TEST_EMPLOYEES`; no schema change) and `isHiddenFrom($viewer)`: SuperAdmin and test accounts are hidden from any viewer who is not a SuperAdmin, never from themselves.
  - `controllers/admin/AdminEmployeesController.php`: the six SuperAdmin-only checks (list filter, form, `_childValidation`, `processSave`, single and bulk delete/status) now use `isHiddenFrom()` (rule 26: one rule). The list also leaves out test accounts. A SuperAdmin's form has a **Test account** switch, saved in `afterAdd`/`afterUpdate`.
  - **Limit:** hidden from the Employees page only. A test account's name can still appear where the platform names employees on records (for example Inbox "Handled by", booking history).
  - Verified locally: admin set staff.test as a test account ("Successful update", stored `[3]`); signed in as the Hotel Manager, the Employees list left it out but showed the manager's own row; opening it by address gave no form; the switch is not on the manager's form.
- **Title-only names on the website:** `AuthController::processSubmitAccount()` and `IdentityController` refuse a first or last name that is only a title, using `Customer::isTitleOnly()` like the back office.
- **Dark "Filters:" bar:** `#selected_filter_container` and its filter chips in `admin-theme.css` use the tokens instead of `#fff` and pale blue (rule 11).
- **Occupancy period one day behind:** reproduced with a stale range (19 Oct–19 Nov); on the next Dashboard load the saved range was reset to today and the panel read "20 Nov". Working as designed; no change.
- **Cleanup batch 1:** `modules/qlopaypalcommerce` deleted (rule 28). Evidence: no reference outside its folder except the "Not used" list on the Modules page (entry removed); not installed locally; no PayPal tables. It had never been installed on the live site.
- **Data clean-up tool:** owner answers recorded in `CLEANUP-PLAN.md` (6 years, recycle bin 90 days, automatic drafts/carts/logs). Not built yet.
- **Not tested:** website sign-up with a title-only name in a browser (code path only); the Filters bar by screenshot; production.
- **Admin Guide error 500 (found on the live site during the phone review):** `admin_guide.tpl` line 268 had `person's` unescaped inside `{l s='…'}`, added on 19 November with the employee alerts text. The page failed for every role that may open it. Reworded ("the phone of each person"). `docker/smoke-test.sh` now scans Salisberg module templates for a `{l s='…'}` string ended early by an apostrophe; shown failing on the old file (line 268) and passing on the fixed one. Smoke 32 of 32.
- **SMS alerts:** the owner confirmed on 2026-11-20 that booking alert texts arrive on their phone (mNotify live).
- **Production phone review (2026-11-20, read only, 390px, Hotel Staff, Hotel Manager, SuperAdmin, and the public website):** no page scrolls sideways, no script errors. Findings and proposals P-18 to P-23 are in `SIMPLIFY-REVIEW.md`.

### 2026-11-20 — Phone review follow-up: P-18, P-22, P-23

- **P-23 (Dashboard):** `modules/dashavailability/views/js/dashavailability.js`: the y scale is `forceY([0, yMax])` with ticks every 5 (10 above 50), `yMax` the next round number above the highest value; before, nvd3 ended the scale at the value itself, so a flat line sat on the top edge. `modules/dashperformance/.../dashboard_zone_two.tpl`: a small script folds Performance below 768px; the heading toggles it. `overrides.css`: below 768px the period button group is one row that scrolls sideways (kept as buttons, not a select, so the stock date script is untouched).
- **P-18 (Housekeeping):** `housekeeping.tpl` cells get `sb-hk-*` classes; `frontdesk.css` (`?v=16`) lays each row out below 768px as a grid card (room and status, type, guest, buttons in one row; floor and last change hidden). Selectors carry `#content.bootstrap` to beat the stock table padding and borders.
- **P-22:** `salisbergnotify/views/js/phone-prompt.js`: "Not now" is stored in `localStorage` with today's date, so the box returns the next day instead of every new browser session. `salisbergguide/views/js/install-app.js`: in the back office the bar shows only on the Dashboard; Close is stored as a timestamp and lasts 30 days (an old stored `1` is converted to now, so nobody who closed it before sees it again at once).
- **Verification (local, headless Chrome at 390px, administrator):** Performance has `sb-folded`; the period row is 29px high; the chart has ticks 0–25 and its line is drawn; Housekeeping went from 5,640px to 3,592px for 22 rooms (screenshot reviewed). No script errors. The phone prompt did not appear for this account locally (it has a phone saved), so "Not now" was not exercised in the browser; the bar's Dashboard-only rule was not exercised either (headless Chrome offers no install prompt).
- **Not tested:** the live server; a real phone; light mode.

### 2026-11-20 — Phone review follow-up: P-19, P-20, P-21

- **P-19 (New booking):** `frontdesk.css` (`?v=17`), below 768px: the date and guest form is a six-column grid (dates span 3, the three numbers span 2), inputs narrower; inside `.sb-board-layout` the room groups come first and the Selected room panel after them (the fixed Book now bar already carries the choice). The 479px rule no longer forces the form to one column. Dates keep the phone's own display format (it comes from the browser, not the page).
- **P-20 (lists):** `sb_phone_cards` is assigned in `AdminProductsController::renderList()`, `AdminAddHotelController::renderList()` and `AdminEmployeesController::renderList()`. `overrides.css` adds their columns to the existing card map: name, hotel name or first and last name on the first line, price on the right, rooms, guests, city or profile below, then Active. Three optional room-type columns (shown on screen at all times, hotel name, advance payment) are given `display: none !important` on the card, because the list script shows optional columns with an inline style.
- **P-21 (Reports):** `reports.tpl` gives each cell `data-label` with its column name; `reports.css` (`?v=2`) turns each row into a card below 768px, label on the left and value on the right.
- **Verification (local, headless Chrome, 390px, administrator):** no page scrolls sideways; New booking: first room at 662px (about 1,400px on the live site), screenshot reviewed; Room types 8 cards, Hotel Details 1, Employees 3, with only the mapped cells visible (checked cell by cell); Reports, Bookings for the month: 7 labelled cards, screenshot reviewed. No script errors.
- **Not tested:** a real phone, light mode, the live server, and the Book now bar after choosing a room further down the list.

### 2026-11-20 — Data clean-up tool (`salisbergcleanup`); vendor Data Cleaner removed

- **Owner:** build the tool from `CLEANUP-PLAN.md` with the answers of the same day (keep paid bookings 6 years, recycle bin 90 days, automatic drafts/carts/logs), then "delete the old one". So it is a new module of ours and `modules/qlocleaner` is deleted rather than renamed in place. Evidence it was safe: never installed, no reference outside its folder (searched `*.php`, `*.tpl`, `*.js`, `*.sh`), not in `ConfigurationTest.php`.
- **Files:** `salisbergcleanup.php` (tab `AdminSalisbergCleanup` under Advanced Parameters, `AdminTools`; no profile is granted it; hook `actionAdminControllerSetMedia` for the daily run; `SBCLEAN_AUTO` on by default; the vendor `checkAndFix()` with its queries kept), `classes/SalisbergDataCleanup.php` (one `select()` per kind used by count, sample and remove so they cannot disagree; `BATCH` 500 per run; a minimum age per kind; every run logged with who), `controllers/admin/AdminSalisbergCleanupController.php` (SuperAdmin only in `viewAccess()`; token-checked posts; removal only when the typed number equals the current count), `views/templates/admin/cleanup.tpl`, `views/css/cleanup.css`. Installed by `docker/setup-modules.php`; `MODULES_VERSION=35`.
- **Kinds:** unfinished drafts (active, no booking; releases the room hold and marks the draft discarded), abandoned carts (no booking, not an active draft; their `htl_cart_booking_data` rows with no order are deleted first), logs, closed Inbox conversations, guests with no booking, no booking line and nothing in a cart. "Recycle bin, removed for good" is shown but locked: it needs 90 days in the bin and a booking older than 6 years, which nothing meets before 2032, so permanent removal of a booking was not written yet.
- **Removed on purpose:** the vendor's two "delete everything" buttons and "Clean & Optimize" (it deleted old carts without releasing their rooms, deleted cart rules, and renumbered every menu tab, which would undo the menu order set on 2026-10-16). From the kept integrity check, the rule deleting `htl_booking_detail` rows whose room type no longer exists was taken out: deleting a room type must never erase booking history.
- **Name clash found:** PHP class names ignore case, so the module class `Salisbergcleanup` and a helper `SalisbergCleanup` could not both exist (fatal on install). The helper is `SalisbergDataCleanup`.
- **Verification (local):** local rows aged by SQL for the test. Signing in ran the automatic clean-up once: 25 abandoned carts (including cart 54 with its room hold) and 3 logs older than 90 days removed, `SBCLEAN_LAST_AUTO` set to today, both logged. On the page: the summary table; closed messages older than 365 days showed 1; typing 99 was refused with the right number; typing 1 removed it ("1 removed.", logged with the administrator's name); the guest age was raised to the 90-day minimum; the recycle bin showed the lock and no Remove button. All 152 bookings untouched. As Hotel Manager: no menu entry, "Access denied." by address. No script errors.
- **Not tested:** the drafts kind (no active draft locally), guests actually removed (previewed only), the database check button after the change, more than 500 items in one run, the live server. On the live site the first automatic run will remove every abandoned cart older than 30 days, as decided.
- Guides: Admin Guide section 16 (table row and a "Data clean-up" subsection, SuperAdmin only); What's New (SuperAdmin); CHANGELOG; rule 25 table.

### 2026-11-20 — Overrides folded (O1–O7); Recycle bin, Statuses and Positions moved to Administration

- **Overrides (CLEANUP-PLAN.md, rule 11):** each override's code now sits in the class it changed; `override/` holds only `index.php`, `.htaccess` and the readme.
  - O1 `AdminOrdersController::init()` (new in the core controller): `addorder`/`submitAddOrder` redirect to New booking.
  - O2 `MailCore::Send()`: returns at once for a `Customer::NO_EMAIL_DOMAIN` placeholder address.
  - O3 `AdminDashboardControllerCore::keepChartsOnAUsefulPeriod()`, called first in `renderView()`.
  - O4 `TranslateCore`: the three original methods are renamed `translateAdmin()`, `translateModule()`, `translatePdf()` (protected); the public `getAdminTranslation()`, `getModuleTranslation()`, `getPdfTranslation()` call them and pass the text through `rebrand()`. Same rule, one place.
  - O5 `AdminImportControllerCore`: `.xlsx`-only check at the start of `ajaxProcessuploadCsv()`, `convertXlsxUpload()`, `xlsxRowToCsvLine()`, and the SimpleXLSX `require_once`.
  - O6 deleted, not folded (rule 28): `ajaxProcessFindOrCreateGuest()` was only called by the guest box on the old order form, which O1 always redirects.
  - O7 `ContactControllerCore::checkContactForm()`, called first in `postProcess()`; the override's `return;` became `return false;`.
  - `docker/smoke-test.sh` fails on any PHP file under `override/`.
- **Menu (owner):** `Salisbergguide::moveToAdministration()`, once (`SBGUIDE_ADMIN_MOVES`), moves `AdminSalisbergRecycleBin` and `AdminStatuses` from Bookings and `AdminModulesPositions` from Modules and Services to the end of Administration, each only while still in its original section; logged. New installs create the Recycle bin under Administration (`Salisbergfrontdesk::TAB_RECYCLE_PARENT`). `MODULES_VERSION=36`. Admin Guide paths and What's New updated.
- **Verification (local):** the class index rebuilt with the core classes (`get_parent_class('Mail')` is `MailCore`). O1: `AdminOrders&addorder` lands on the Room board. O2: a send to a placeholder returns true without sending. O3: a saved September range was reset to today on sign-in. O4: "QloApps version:" reads "Salisberg version:" in admin, module and PDF text; "qloapps.com/addons" is kept. O5: the sample `hotels_import.xlsx` became a semicolon CSV named `.csv`; a `.csv` name is refused. O7 (the attack tests of 2026-10-13): an `http://` and a `www.` link refused, a list value and a quote in the token refused, a plain message stored (thread 6) with its generated title. Menu: the three entries are under Administration at positions 7–9 and the move was logged. Smoke test 33 of 33, including the new override check.
- **Not tested:** the Excel upload through the browser (the conversion was run directly); the live server.

### 2026-11-20 — List SQL buttons removed; phone menu icon

- **Owner report (phone and desktop):** the list toolbar's ">_" button showed the full SQL query behind the list, and the database button exported it to SQL Manager.
  - The buttons showed for any profile with view on SQL Manager, and the query was always in a hidden form field in the page source.
  - Removed for everyone (rule 28): the two buttons and the hidden `sql_form_*` in `admin/themes/default/template/helpers/list/list_header.tpl`, the `sql` value in `HelperList::displayListHeader()` (`classes/helper/HelperList.php`), and `createSqlQueryName()` in `js/admin.js` (its only caller was that button). Searched all templates and scripts: no other copy.
  - The developer team keeps Advanced Parameters › SQL Manager.
- **Phone menu button:**
  - `admin/themes/default/template/nav.tpl` used `icon-align-justify icon-rotate-90`, which drew vertical bars.
  - The template and the two places in `admin/themes/default/js/admin-theme.js` that redraw it after closing the menu now use `icon-reorder` (≡).
- **Verification (local, 390px, administrator):** the button shows ≡, opens the menu (icon becomes ×) and closes it (≡ again). The Bookings toolbar has 3 buttons and no SQL button or hidden query. No script errors. Smoke test passes.
- **Not tested:** other list pages one by one (they share the one template); the live server.

### 2026-11-20 — Light/dark switch on a phone

- **Owner report:** the switch looked broken on a phone. On the live site at 390px `#sb-theme-toggle` was `display: block`, so the two icons stacked (measured: both at x 249, y 11 and 25). It had no display rule of its own and relied on a top-bar link rule that the phone layout replaces.
- **Fix (`overrides.css`, the switch's own rule):** `display: inline-flex; flex-wrap: nowrap; align-items: center; box-sizing: border-box`, and `:focus:not(:focus-visible) { outline: none }` so a tap leaves no black ring while keyboard focus still shows.
- **Verification (local, 390px, 3x zoom screenshots):** icons side by side (x 249 and 273) in light and dark, the knob slides, no ring after a tap.

### 2026-11-20 — Clean-up batches 2, 3, 4 and 6

- **Owner:** complete the batches (the other agent was not active).
- **Removed (27 module folders, rule 28):**
  - Batch 2: `bankwire`, `cheque`, `qlochannelmanagerconnector`. `qlohotelreview` is kept, because it is switched off but may be switched on (rule 28).
  - Batch 3: the 20 `stats*` modules, `sekeywords`, `pagesnotfound`, `qlostatsserviceproducts`. `graphnvd3` and `gridhtml` are kept: the Stats screens load them by name (`PS_STATS_RENDER`, `PS_STATS_GRID_RENDER`).
  - Batch 4: `dashactivity`, `dashproducts`, `dashgoals`, `dashinsights`.
  - Theme copies of the bank wire and cheque templates and their two icons.
- **Evidence:**
  - Whole-tree search for each name, excluding cache, .git, audit, history, translations and compiled vendor CSS.
  - The remaining callers all check existence first: `AdminReferrersController` (statsdata), `AdminStatsTabController` (statsforecast) and `AdminProductsController` (statsproduct). The room type "Statistics" button code was removed.
  - None is used by the website or a booking. No local booking was paid through bank wire or cheque.
- **References removed:**
  - The installer module list (`install/models/install.php`), the themes native list (`AdminThemesController`) and the theme module/hook list (`config/xml/themes/default.xml`).
  - The page entries in `install/data/xml/meta.xml`, the mail list in `Language.php`, and the payment styles in the theme's `global.css`/`global.scss` (`.cheque-box` is kept: Salisberg Pay uses it).
  - The Dashboard list in `salisbergfrontdesk::replaceLegacyTodayWidget()`, the reload branch in `js/admin/dashboard.js`, the `#dashproducts` rule in `overrides.css`, the channel manager entry in `$hiddenMenus`, and the "replaced by ours" list on the Modules page (the folded group is now "Not installed").
- **Database (live site), `docker/setup-modules.php`, `MODULES_VERSION=37`:**
  - A removed module cannot uninstall itself. For each one whose folder is gone and whose row still exists, the step deletes its module, shop, group, country, currency, hook and preference rows, its menu tabs with their rights, its settings, and its `module-<name>-*` page entries.
  - It drops its own tables only when empty (the 404 log `pagenotfound` was kept locally because it holds rows). Each removal is logged.
- **Fault fixed on the way (`classes/order/OrderHistory.php`):**
  - Marking a booking paid read the payment name from `$payment_method->displayName`, but the first invoice loop overwrote `$payment_method` with the name, so a second invoice read a property of a string. It also failed outright when the payment module no longer exists.
  - The name is now taken once (`$payment_name`), falling back to `$order->payment`.
- **Batch 6:** 15 unreferenced theme images removed (each also searched by part of its name). The 46 non-English module translation files are kept: they are needed if a second language is added.
- **Batch 5:** proposed in `CLEANUP-PLAN.md`, waiting for the owner. It includes the unreachable old order form.
- **Verification (local, image rebuilt):**
  - The setup log showed all 27 removed. Smoke test 33 of 33.
  - Marking a Pay-later booking paid recorded "Back office order". A booking switched to the removed `bankwire` module recorded its saved name "Bank wire (old)", with no error.
  - Dashboard as SuperAdmin, Hotel Manager and Hotel Staff: every box finished loading, no PHP or script errors.
  - Manage Modules: 32 modules (was 58).
- **Not tested:** a website checkout to the payment step (the smoke test loads the pages only); the live server, where the step runs on the next deploy.
- Also in this change: the light/dark switch fix recorded above.

### 2026-11-20 — Clean-up batch 5: old order form, supplier/manufacturer/supply-order screens, comparison code

- **Owner:** "do batch 5".
- **Old "Add new order" form:**
  - Removed `admin/themes/default/template/controllers/orders/form.tpl` (2,531 lines), the `submitAddOrder` branch of `AdminOrdersController::postProcess()` (142 lines), and `renderForm()` (100 lines). `renderForm()` now only redirects to New booking.
  - Also removed `_new_service_product.tpl`, which nothing included.
  - **Evidence:** `init()` already redirected `addorder`/`submitAddOrder`; `renderForm()` is reached only through the add screen; the form's guest endpoint went with O6. The other order partials are used by the booking page or by `AdminCartsController`.
  - **Kept:** the `AdminCartsController` cart helpers, because the hidden legacy room-booking page (kept for reallocation) still reaches them.
- **Suppliers, manufacturers, supply orders:**
  - Removed their three admin controllers and five template folders, plus the commented-out installer tab lines.
  - **Evidence:** no menu entry (commented out in `install/data/xml/tab.xml` since installation; none in the database); no reference outside their own files.
  - **Kept:** the `Supplier`, `Manufacturer` and stock classes, because product and room code read them.
- **Comparison:**
  - Removed `classes/CompareProduct.php`, the lookup and the two values in `FrontController`, the comparison script values and commented-out button in the theme's `product-list.tpl`, and the 28-line commented-out block in `CategoryController`.
  - **Evidence:** no script reads `comparator_max_item`, `comparedProductsIds`, `min_item` or `max_item`. The front pages were removed on 2026-10-13. The `compare` tables and the `PS_COMPARATOR_MAX_ITEM` setting stay; they are harmless data.
- **Hidden admin tabs** (Groups, Titles, Shopping Carts and others): kept hidden. Several are used by other screens (carts by bookings, groups by prices).
- **Verification (local, image rebuilt):**
  - Smoke test 33 of 33.
  - `AdminOrders&addorder` lands on the Room board, and a booking page opens with no errors.
  - Website: search results, a room page, and the hotel room list (category 8, with dates) render with rooms and no errors. Category 2 is the root and returns 404, as it should.
  - No script errors.
- **Not tested:** a full website booking; the live server.

### 2026-11-20 — Mail server (SMTP) on the Integrations page

- **Owner:** add SMTP to the integrations page so it can be entered in the app.
- **Why it was missing:** `AdminEmailsController::initContent()` has `renderOptions()` commented out, so Advanced Parameters › E-mail shows no settings form. Mail could not be configured without the database.
- **What:**
  - In `AdminSalisbergIntegrationsController`: `saveMail()` writes `PS_MAIL_METHOD` (2 = SMTP, 3 = off), `PS_MAIL_SERVER`, `PS_MAIL_SMTP_PORT`, `PS_MAIL_SMTP_ENCRYPTION`, `PS_MAIL_USER` and `PS_SHOP_EMAIL`, plus `PS_MAIL_PASSWD` only when a password is typed. These are the platform's own settings, so every email uses them (rule 26).
  - `sendTestMail()` sends through `Mail::sendMailTest()` and logs the server's reply on failure.
  - The panel `#sb-int-mail` is in `integrations.tpl`. The Email card points to it and is green only for a saved SMTP server.
  - SuperAdmin only, as the rest of the page.
- **Guides:** Admin Guide section 10 (where and what to enter; any SMTP provider), the section 9 row and the alerts paragraph now point to Administration › Integrations; CHANGELOG.
- **Verification (local, browser, administrator):**
  - Invalid values were refused with plain messages.
  - Valid values saved and the panel showed email on.
  - A test to a non-existent server failed with the plain message and a log entry.
  - Switching off saved method 3.
  - `php -l`; no script errors.
- **Not tested:** a real mail server (needs the mailbox password); the live server.

### 2026-11-20 — Live test, new-customer sign-up on desktop and phone; phone search fix; names tidied

- **Live back office (developer account, phone width, read only):**
  - Manage Modules lists 32. The Bookings list has no SQL buttons. The old add-order address lands on New booking.
  - Recycle bin is under Administration. Data clean-up is present and its daily run has emptied old carts.
  - The Email panel is not there yet (not pushed).
- **Live sign-up as a new customer** ("Test Signup", test.signup.*@example.com), stopped after the payment methods, no booking made:
  - **Desktop (1440):** search, Book Now, checkout, guest form (new account), payment methods. About 63 seconds, no errors.
  - **Phone (390): failed at search.** After the dates are picked, the guest picker opens inside the search pop-up. It is absolutely positioned inside a scrolling box, so it covered Search Rooms and was cut off, and a tap on Search Rooms did nothing.
  - Fixed in `modules/wkroomsearchblock/views/css/wk-global-search.css` (rule 11): inside `.fancybox-wrap` the picker is static, so it pushes the button down.
  - Locally the phone flow then ran to the payment methods in about 63 seconds with no errors (screenshot reviewed).
- **"Add to home screen" bar:** it covered the bottom of the checkout form on phones. `install-app.js` now offers it on the website homepage only (`body#index`). Checked: shown on the homepage, not at checkout.
- **Guest names (owner approved):**
  - `docker/setup-modules.php`, once (`SB_NAMES_TIDIED`, `MODULES_VERSION=38`), applies `Customer::tidyName()` to live customers and their addresses and logs the count.
  - Title-only names are counted, not guessed.
  - Checked: "ama mensah" became "Ama Mensah" and "KOFI" became "Kofi"; one title-only name was counted.
- **Email wording:** Zoho examples removed from the Email panel, its messages and the guide; any SMTP provider works.
- **Live data left by the test:** one or two customer accounts named "Test Signup" with @example.com addresses (no booking). Their carts are removed by the daily clean-up after 30 days.
- Smoke test 33 of 33.

### 2026-11-20 — Back office on a phone: menu, empty "Menu" button, Add to home screen

- **Owner report:** on a phone there is no button to add the app, "only 2 items", and a Menu on the Dashboard that does nothing.
- **Phone menu:**
  - Below 768px the theme script renames `#nav-sidebar` to `#nav-mobile`, so none of the menu rules applied there. The group labels showed as plain text, the fold arrows as grey dashes, "System" as a grey pill, and the list did not scroll (the developer account's menu was 930px of content in a 796px box on the live site).
  - `overrides.css` gains `#nav-mobile` rules: labels styled, System a plain label with every section shown, fold arrows hidden (a tap opens the section's pages), and the list scrolls.
  - Hotel Staff see four sections by design (P-17); the developer account sees twelve.
- **Empty "Menu":**
  - Its only item was the vendor "Recommendations" button, which `overrides.css` hid everywhere.
  - Removed at the source (rule 28): `AdminController::addPageHeaderToolBarModulesListButton()` and its call; the template block, its loader script and a commented-out Help block in `page_header_toolbar.tpl`; and the two CSS hide selectors.
  - The template shows the phone "Menu" only when it has a button other than Back.
- **Add to home screen:**
  - New item in the account menu (`header.tpl`, `#sb-install-item`), unhidden by `install-app.js` on every back office page unless the app is already installed. It opens the browser's install prompt when there is one, and otherwise shows the steps (`data-mode="help"`: Safari Share on an iPhone, the browser menu elsewhere).
  - Staff Guide section 1 and Admin Guide section 1 updated.
- **Verification (local, 390px):**
  - The menu screenshot was reviewed: labels, no dashes, all sections.
  - The menu scrolls (max-height 784px, overflow auto).
  - The Dashboard has no "Menu"; Bookings keeps its Menu.
  - The account item is visible. With a prompt available, it opens the prompt. With the prompt blocked and an iPhone user agent, it shows "In Safari, tap Share, then Add to Home Screen" (screenshot reviewed).
  - No script errors.
- **Not tested:** a real Android phone and a real iPhone; the live server.

### 2026-11-20 — P-24: shorter website checkout (guest booking, no title, email optional)

- **Owner approved** (SIMPLIFY-REVIEW P-24), then refined: "email should be there and not required".
- **How it works:** it uses the platform's own guest checkout rather than a new path (rule 26).
  - `docker/setup-modules.php` switches `PS_GUEST_CHECKOUT_ENABLED` on once (`SB_GUEST_CHECKOUT_SET`, `MODULES_VERSION=39`), so it can be switched off in Preferences and stays off.
  - `themes/hotel-reservation-theme/order-opc-new-account.tpl`: the two "Guest checkout / Create an account" buttons became one line, "Booking as a guest: no password needed. Create an account instead". The social title select is removed and first and last name take half the row each. Email is no longer marked required; the label says it is optional for a guest and needed for the emailed invoice.
  - `js/order-opc.js`: with guest checkout on, the form opens in guest mode (password hidden, `is_new_customer=0`, submit as `submitGuestAccount`). "Create an account instead" shows the password and submits as an account.
  - `controllers/front/AuthController.php::processSubmitAccount()`: for a guest who gives a phone and no email, the email becomes the walk-in placeholder `guest-<time>-<4 digits>@guest.invalid` (`Customer::NO_EMAIL_DOMAIN`). `Mail::Send` never sends to it; the booking text goes to the phone. An account still needs a real email (the customer model requires it).
  - Styles: `salisberg.css` (`?v=18`).
- **Guides:** Staff Guide (Customers row: website guests may have no email); What's New; CHANGELOG.
- **Verification (local, headless Chrome):**
  - Phone (390), phone-only guest: no password, no title. It went through to payment and then to a confirmed cash booking, `SB-261120-01`. The customer has `is_guest=1`, the placeholder email and the phone. The confirmation page shows the reference, amount and payment steps with no email wording.
  - A guest with an email is saved as a guest with that email.
  - "Create an account instead" shows the password and saves an account (`is_guest=0`).
  - Desktop (1440), phone-only guest: to the payment methods, no errors.
- **Known limit:** "Track my booking" asks for an email, so a guest who gave none cannot use it. They have the reference by text and can call the hotel.
- **Not tested:** the live server; a guest coming back later with the same phone number (each guest booking is its own guest record, as the platform does for guests).

### 2026-11-20 — End-to-end browser tests (`tests/e2e`); general skill in the shared library

- **Owner:** go ahead with the library suggestions in order (e2e tests, then characterization tests, then a general skill).
- **Evaluation (workflow `e2e-testing`):**
  - Playwright fits: a web app, Node 22 on the machine, and the installed Chrome via `channel: 'chrome'`, so no browser download. Maestro is for native mobile apps.
  - The existing tests were `docker/smoke-test.sh` only (page loads, no browser).
  - There is no CI (Coolify only deploys), so the suite is local, and that gap is documented.
- **Why a new folder (rule 25):** nothing existing runs a browser. It is dev-only: excluded from the image (`.dockerignore`: `tests`), with dependencies and output git-ignored. `@playwright/test` is pinned at 1.64.1.
- **What:**
  - `playwright.config.js`: desktop 1440 and phone (Pixel 7). It refuses any base address that is not `http://localhost`.
  - `support/global-setup.js` and `support/seed.php`, run in the app container each time: test employees `e2e.manager@example.com` and `e2e.staff@example.com`, marked as test accounts, plus the hotel category; the sign-in limiter is cleared.
  - `support/helpers.js`: sign-in, and opening an admin page through the token link while skipping "My preferences".
  - Three spec files, described in `tests/e2e/README.md`.
- **Verification:**
  - Full run: 8 passed, 6 skipped (desktop-only tests in the phone project), about 5.6 minutes. Bookings were created for dates 200–330 days ahead.
  - Negative control: with the pre-fix search stylesheet (`git show 811ae396~1`) in the container, the phone search test failed ("Expected true, received false"). Restored, it passes.
- **Found while writing the tests:**
  - Names refuse digits, so the test people are "Test …".
  - The sign-in page has two `submitLogin` buttons.
  - Hotel Staff and Hotel Manager opening Employees land on their own profile (allowed by the platform).
  - The first `AdminEmployees` link on a page is "My preferences".
- **Shared library** (outside this repository, `Workflow and skills`):
  - New general skill and workflow `inherited-web-app-maintenance`: fix at the source, prove dead code, versioned versus every-start deploy steps, verify in a running stack, and a list of traps stated by mechanism.
  - README rule 12 (owner): name skills by the job, not the project. `AGENT-INSTRUCTIONS.md` was aligned with it.
- **Not tested:** the suite on another machine; Mobile Money, bank transfer and part payments; check-in and check-out from a booking.

### 2026-11-20 — Rule tests for the money and safety rules (`tests/php`)

- **Owner:** suggestion 2 from the library review (`characterization-testing` workflow).
- **Why a runner of our own (rule 25):** the project has no Composer and no PHPUnit, and adding them would add a build step. `tests/php/run.php` is about 70 lines: `check()`, `same()`, and `rolledBack()`, which wraps writes in a transaction that is always rolled back. It sets the context a web request has (currency, cart, customer, country); without it the price code stops with "Fatal error". `run.sh` copies the folder into the container and runs it. It is excluded from the image (`tests` in `.dockerignore`).
- **Pinned (10 tests, 49 checks):**
  - booking codes;
  - name tidying and title-only names;
  - `Validate::containsLink` and `isName`;
  - the placeholder email and `Mail::Send`;
  - the payment cap after a part payment;
  - the payment-name fallback for a removed module;
  - a room's own price, desk against website;
  - Data clean-up minimum ages, keep periods and the locked bin;
  - a characterization of the known gap that Track my booking needs an email (to be inverted by the change that fixes it).
- **Verification:**
  - All 10 pass.
  - Negative control: with the cap removed from `OrderHistory.php` in the container, the payment test failed ("expected 208.0, got 308.0"). With it restored, all pass.
  - No trace: order payments, own prices and the code counter were 53/3/9 before and after.
- **Not covered:** the Add payment over-the-balance refusal (it is controller code; the e2e suite is the place for it); the tax switch on a booking.

### 2026-11-20 — Phone account menu as a bottom sheet

- **Owner:** take inspiration from Dribbble and improve the profile pop-up on mobile. The standard Dribbble/iOS/Material pattern is a bottom sheet: a grab handle, a header showing who is signed in, 48–56px rows, the destructive action last and in red, and the page dimmed behind.
- **Before (below 768px):** the stock `admin-theme.css` drew `#employee_links` as a full-screen panel (`position: fixed; top: 36px; height: 100%; background: #383F50`, all `!important`) with uppercase, blue-icon rows: three items floating in an empty dark screen.
- **Change:**
  - The three stock rules were removed from `admin-theme.css` (rule 11), not out-ranked.
  - `overrides.css`, inside the existing `max-width: 767px` block: the menu is a fixed bottom sheet (18px top corners, a handle, `max-height: 80vh`, safe-area padding), with 52px rows, Sign out in `--sb-danger` above a divider, and a backdrop drawn by `#employee_infos.open::after`. Tapping outside closes the menu through Bootstrap's own document click. The trigger's grey "open" background was cleared.
  - `header.tpl`: a `li.sb-me-head` with the initials avatar, name and email, hidden from 768px up.
- **Verification (local, Playwright, Pixel 7 width, Hotel Manager test account):** in light and dark mode the sheet is 272px tall and its bottom edge sits at the bottom of the screen; tapping outside hides it. Both screenshots were reviewed. Smoke test 33 of 33.
- **Not tested:** a real iPhone (the safe-area inset); RTL (`admin-theme_rtl.css` still has the old rules); the live server.

### 2026-11-20 — Browser "save password" on sign-in

- **Owner:** check that saving or remembering the password in the browser works.
- **What was wrong:** the website sign-in page (`authentication.tpl`) is an ordinary form post, which browsers recognise. The back office sign-in (`js/admin/login.js`, `action="#"`, posted to `ajax-tab.php`) and the checkout sign-in (`order-opc.js`, `#SubmitLogin`) send the password by script and then reload or redirect the page. Chrome only sometimes offers to save after such a sign-in, and Firefox and Safari usually do not. No sign-in field set `autocomplete`.
- **Fix (inherited files, changed in place):**
  - After a successful sign-in, both scripts call `navigator.credentials.store(new PasswordCredential({id, password}))` where the browser supports it (Chrome, Edge, Android). The back office redirect waits for the call and goes ahead whether it succeeds or fails.
  - `autocomplete="username"` / `current-password` on the back office and website sign-in fields, and `new-password` on the account-creation password fields (`content.tpl`, `authentication.tpl`, `order-opc-new-account.tpl`).
  - Nothing is stored by us: the browser asks the user, as it does for any form.
- **Verification (local, Playwright with Chrome, desktop):** the back office fields carry the new attributes; a Hotel Manager sign-in passed that email and password to `navigator.credentials.store` and reached the back office; the role and security tests pass (5 of 5).
- **Not tested:** the save prompt itself, because the automated browser does not show it (try it by hand in Chrome once it is deployed); Safari and Firefox, which do not support `PasswordCredential` and rely on the `autocomplete` hints and their own heuristics; the checkout sign-in in a browser; the live server.
