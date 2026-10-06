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
20. **Anything written at runtime outside `img/`, `upload/`, `download/` and `/data` is lost on deploy.** When adding or enabling a module that accepts uploads, add its folder to `PERSIST_DIRS` in `docker/entrypoint.sh`. (Background: change log, 2026-10-06.)
21. **Sanitise input and escape output.** Take request input through `Tools::getValue()`, cast numbers, use `pSQL()` or `(int)` for anything placed in SQL, and escape template output with `|escape:'html':'UTF-8'`.
22. **Deploy-time data steps must not assume production matches local.** Read the current state, act on it, and log what was actually changed.
24. **Security changes to vendor files go in `PATCHES.md`, in the same change,** with the upstream commit they mirror, so they can be dropped when upstream ships the fix. Audit reports and lists of unpatched issues stay in the git-ignored `/audit/` folder: this repository is public.

### Guides

23. **Every feature must be documented in its guide, in the same change.** The back office guides live in `modules/salisbergguide/views/templates/admin/`. A feature is not finished until the guide is updated.
    - **Who uses it decides where it goes.** Something front desk staff do goes in `staff_guide.tpl`. Something only an administrator can do or configure goes in `admin_guide.tpl`. A feature with both sides (for example a payment method: staff record payments, admins configure it) is covered in both, each from its own side.
    - **Never put admin-only instructions in the Staff Guide.** Staff must not be shown how to do things their role cannot do. If the feature adds a page staff need, also grant it to the Hotel Staff profile (`$staffAccess` in `salisbergguide.php`) and list it in the guide's menu table.
    - **Write what is on the screen.** Use the exact menu path, button and tab names as rendered, and check them against the running back office before writing. Add the section to the guide's table of contents.
    - **Changes and removals count too.** If a feature is changed, renamed, moved or removed, update or delete its guide text in the same change.
    - **Record it.** The change log entry for the feature must name the guide section that was added or updated.

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
