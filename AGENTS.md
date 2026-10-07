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

Do not modify third-party dependency licenses or license notices belonging to external projects.

Do not modify historical changelogs or archived documents unless they incorrectly claim that the current version of this project is licensed under OSL.

Preserve existing copyright notices and attribution for third-party code.

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

11. **Fix things where they are defined.** The inherited platform code is ours (section 1, Direction), so a fault or an unwanted default in an inherited file is corrected in that file, not papered over from somewhere else. For styling this means changing the inherited stylesheet's rule instead of out-ranking it with `!important`. New behaviour still goes in `override/` and in modules, as PrestaShop 1.6 intends, because that keeps our features in one findable place; an override is no longer preferred merely to leave the original untouched. Every change to an inherited file is verified in a running stack and recorded in section 6.
12. **Do not rename the `qlo_` table prefix or internal `QloApps`/`PrestaShop` identifiers** as part of branding work. Branding is done through shop name, theme, logos and translations.
13. **Match the surrounding code style** (PrestaShop 1.6 conventions: PSR-2-ish PHP, Smarty `.tpl`).
14. **Keep the upstream license headers** in files you touch (OSL-3.0 requirement).

### Process

15. **Verify before claiming.** Build the image and boot the stack locally (section 5) after any change to `Dockerfile`, `docker/` or compose files. Record what was and was not tested.
16. **Commit or push only when the owner asks.** Work on a branch, not directly on `main`.
17. **Record every meaningful change in section 6** with what, why and how.
18. **Upstream is a source of security fixes, not of releases.** Do not merge `Qloapps/QloApps` (neither `develop` nor a new tag) and never install the 1-Click Upgrade module. Do watch upstream's security advisories and fixes: a fix that applies to code we still carry is backported by hand and listed in `PATCHES.md` (rule 24). Bundled libraries (jQuery, Bootstrap scripts, TinyMCE, Smarty, TCPDF) are kept current by us, from their own publishers.
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
    | Checking a running stack after a change | `docker/smoke-test.sh` |
    | Installing, enabling or disabling modules | `docker/setup-modules.php` |
    | One-off data or schema changes on deploy | versioned steps in `docker/entrypoint.sh` |
    | Web server headers and access rules | `docker/apache.conf` |
    | Request limits | `docker/ratelimit.php` |
    | Brand images | `docker/branding/make.php` |

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
