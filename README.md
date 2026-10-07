# Salisberg Hotels

The booking website and back office for **Salisberg Hotels**: <https://salisberg.com>

Guests search for rooms, book, and choose to pay by cash at the hotel or by Mobile Money. Staff take and manage bookings, record payments, and check guests in and out from the back office.

Salisberg is built on [QloApps](https://github.com/Qloapps/QloApps) 1.7.0, an open-source hotel booking engine by Webkul (itself based on PrestaShop 1.6). This repository is that code plus Salisberg's design, features, security fixes and deployment setup.

## What is in this repository

| Part | Where |
|---|---|
| Website design | `themes/hotel-reservation-theme/css/salisberg.css` |
| Back office design, light and dark | `admin/themes/default/css/overrides.css` |
| Cash and Mobile Money payments | `modules/salisbergpay` |
| Back office guides, What's New page, staff roles | `modules/salisbergguide` |
| Docker image, startup steps, backups, request limits | `Dockerfile`, `docker-compose.yml`, `docker/` |
| Security fixes applied ahead of the vendor | `PATCHES.md` |

## Documents

| File | What it is for |
|---|---|
| [`CHANGELOG.md`](CHANGELOG.md) | Every change, newest first, in plain words |
| [`AGENTS.md`](AGENTS.md) | The rules for working on this code, how the setup works, and the detailed record of each change: what, why, how, and how it was tested. **Read it before changing anything.** |
| [`COOLIFY.md`](COOLIFY.md) | Putting the site live on a Coolify server, step by step |
| [`COOLIFY-STAGING.md`](COOLIFY-STAGING.md) | Setting up and using the private staging copy |
| [`BACKUP.md`](BACKUP.md) | Backups, off-server copies, and recovering from a lost server |
| [`PATCHES.md`](PATCHES.md) | Security fixes made to vendor files, with the upstream change each one mirrors |
| [`SECURITY.md`](SECURITY.md) | How to report a security problem |
| [`LICENSE.md`](LICENSE.md) | The licence |

Inside the running back office, **Guides** holds a Staff Guide, an Admin Guide and a What's New page.

## Stack

- PHP 8.1 with Apache, MySQL 8.0, Smarty templates
- No Composer or Node build step
- Docker, deployed with [Coolify](https://coolify.io)

## Run it locally

You need Docker.

```bash
cp .env.example .env        # then set real passwords in .env
docker compose up -d --build
docker compose logs -f app  # the first start installs the site; allow a few minutes
```

- Website: <http://localhost:8080>
- Back office: <http://localhost:8080/admin-salisberg>, signing in with `ADMIN_EMAIL` and `ADMIN_PASSWORD` from `.env`
- Stop it: `docker compose stop`
- Start again from nothing, **deleting all local data**: `docker compose down -v`

## Deploy

Production runs on a server with Coolify, built from the `salisberg-production` branch using `docker-compose.yml`. Settings and passwords are entered in Coolify's Environment Variables; none are stored in this repository. `.env.example` lists every variable.

The full steps, and what must never be done on the production server, are in `AGENTS.md`, sections 2 and 5.

## Who can do what in the back office

| Role | For | Can do |
|---|---|---|
| SuperAdmin | The developer team | Everything |
| Hotel Manager | The person who runs the hotel | Rooms, prices, bookings, guests, website pages, staff accounts, reports |
| Hotel Staff | Front desk and reservations | Bookings, payments, check-in and check-out, guest records |

## Working on the code

- Work on a branch, not on `main`.
- Extend what exists before adding something new; `AGENTS.md` rule 25 lists where each kind of change belongs.
- Do not edit vendor files when an override or a module will do, and never move or rename one.
- Every change is recorded in `CHANGELOG.md` and `AGENTS.md`, and in the guides or the What's New page when users will notice it.
- New versions of QloApps are adopted by tagged release only.

## Security

Please do not report security problems in public issues. See [`SECURITY.md`](SECURITY.md).

## Licence and credit

License and Credit

Salisberg is a modified version of QloApps, © Webkul. QloApps and bundled modules may carry their own respective licenses in their folders.

The changes made by Salisberg in this repository are released under the MIT License. License and copyright notices in the source files are preserved as required.

Third-party code and modules remain subject to their respective original licenses.
