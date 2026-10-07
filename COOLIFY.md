# Deploying Salisberg on Coolify

How to put the live site on a server running [Coolify](https://coolify.io), from an empty server to a working booking site, and how to look after it afterwards.

- For a test copy of the site, see [`COOLIFY-STAGING.md`](COOLIFY-STAGING.md).
- For backups and recovering from a lost server, see [`BACKUP.md`](BACKUP.md).
- For why the setup is built this way, see `AGENTS.md`, sections 3 and 4.

Button and field names below are as Coolify v4 shows them. They move a little between Coolify versions; the meaning stays the same.

## Contents

1. [What you need first](#1-what-you-need-first)
2. [Point the domain at the server](#2-point-the-domain-at-the-server)
3. [Create the resource](#3-create-the-resource)
4. [Give the app its domain](#4-give-the-app-its-domain)
5. [Add the environment variables](#5-add-the-environment-variables)
6. [Deploy](#6-deploy)
7. [Check that it worked](#7-check-that-it-worked)
8. [First things to do in the back office](#8-first-things-to-do-in-the-back-office)
9. [Updating the site](#9-updating-the-site)
10. [Changing the domain later](#10-changing-the-domain-later)
11. [What must never be done on the live server](#11-what-must-never-be-done-on-the-live-server)
12. [When something goes wrong](#12-when-something-goes-wrong)

## 1. What you need first

| Item | Detail |
|---|---|
| A server (VPS) | Ubuntu or Debian, at least 2 GB of memory and 20 GB of disk. 4 GB of memory is more comfortable: the first install is heavy. |
| Coolify installed on it | Follow Coolify's own install page. You should be able to sign in to the Coolify dashboard. |
| The domain | `salisberg.com`, with access to its DNS records. |
| The code | The GitHub repository `gomathie/stayflow-hms`. The live site is built from the branch **`salisberg-production`**. |
| A password manager or a private file | To keep the values from step 5. They are needed again if the server is ever rebuilt. |

Nothing has to be installed on the server by hand. Coolify builds the site from `docker-compose.yml` in the repository and handles HTTPS itself.

## 2. Point the domain at the server

At the company where the domain is registered, add two DNS records:

| Type | Name | Value |
|---|---|---|
| A | `@` (or `salisberg.com`) | The server's IP address |
| A | `www` | The same IP address |

Wait until both names answer with the server's address (`ping salisberg.com`). This can take from a few minutes to a few hours. Coolify cannot issue the HTTPS certificate until they do.

**Not ready to move the domain yet?** Deploy on any address you control first (for example `new.salisberg.com`) and switch later. Section 10 shows how; nothing has to be reinstalled.

## 3. Create the resource

1. In Coolify open **Projects**, create a project (for example `Salisberg`) and open its **production** environment.
2. Click **+ New** (Add Resource).
3. Choose **Public Repository**. The repository is public, so no GitHub connection is needed.
   (If the repository is ever made private, choose **Private Repository (with GitHub App)** and connect GitHub first.)
4. Repository URL: `https://github.com/gomathie/stayflow-hms`
5. Branch: `salisberg-production`
6. **Build Pack: Docker Compose.** This matters: the default (Nixpacks) will not work.
7. Base Directory: `/`
8. Docker Compose Location: `/docker-compose.yml`
9. Continue. Coolify reads the file and lists three services: **app**, **db** and **backup**.

## 4. Give the app its domain

On the resource's **Configuration › General** page each service has a **Domains** box.

- **Domains for app:** `https://salisberg.com,https://www.salisberg.com`
- **Domains for db:** leave empty.
- **Domains for backup:** leave empty.

Write `https://`, not `http://`: that is what tells Coolify to get a certificate. The app listens on port 80 inside its container, which is what Coolify expects, so no port is added.

Click **Save**.

Never give the database a domain or a public port. It is only reachable from the app, and it should stay that way.

## 5. Add the environment variables

Open **Environment Variables**. The quickest way is **Developer view**, which takes them all pasted at once, one `NAME=value` per line.

### Must be set

| Variable | Value | Notes |
|---|---|---|
| `PUBLIC_URL` | `https://salisberg.com` | The exact address guests use. No slash at the end. |
| `MYSQL_PASSWORD` | A long random password | The app's database password. |
| `MYSQL_ROOT_PASSWORD` | A different long random password | The database administrator's password. |
| `ADMIN_EMAIL` | The address you will sign in to the back office with | |
| `ADMIN_PASSWORD` | A strong password | Used once, to create the first back office account. Change it after the first sign-in. |
| `ADMIN_DIR` | For example `desk-7k2p` | The back office lives at `https://salisberg.com/<this>`. Must **not** be `admin`. Pick something that cannot be guessed and do not change it afterwards. |
| `BACKUP_PASSPHRASE` | A long random passphrase | Encrypts the nightly backups. **Keep a copy away from the server.** Without it the backups cannot be read. See `BACKUP.md`. |

### Should be set

| Variable | Value | Notes |
|---|---|---|
| `SHOP_NAME` | `Salisberg Hotels` | Used at the first install. |
| `ADMIN_FIRSTNAME`, `ADMIN_LASTNAME` | Your name | For the first back office account. |
| `SHOP_COUNTRY` | `gh` | Two-letter country code, lower case. |
| `TIMEZONE` | `Africa/Accra` | |

### Can be left out (the defaults are right)

| Variable | Default | When to set it |
|---|---|---|
| `MYSQL_DATABASE`, `MYSQL_USER` | `salisberg` | No reason to change. |
| `AUTO_INSTALL` | `1` | Installs the site the first time only. Leave as is. |
| `SYNC_DOMAIN` | `1` | Keeps the site's address in step with `PUBLIC_URL`. Leave as is. |
| `BACKUP_KEEP_DAYS` | `14` | Days of backups kept on the server. |
| `BACKUP_HOUR_UTC` | `2` | Hour of the nightly backup. Ghana is on UTC, so this is 2 a.m. |
| `PHP_VERSION` | `8.3` | Only to go back to `8.1` if a fault appears. |
| `MYSQL_VERSION` | `8.0` | **Do not set to `8.4` on the live site** without the steps in `AGENTS.md` (change log, MySQL 8.4). It cannot be undone. |

**Do not add `HTTP_PORT`.** It is for a developer's own computer only.

### Rules for the passwords

- Use letters, digits and `- _ . !` only. **No quotes, backslashes, backticks or dollar signs:** the installer refuses them and the deploy stops.
- A good way to make one, on any computer: `openssl rand -hex 24`
- Every value here is a secret. They go in Coolify only, never in the repository.

### The "Build Variable" tick box

Each variable in Coolify has a box named **Build Variable** (newer versions: **Available at Buildtime**). **Untick it for every variable.** None of these values are needed while the image is built, and ticking the box can leave passwords inside the built image.

Save the variables, then copy the whole list to your password manager.

## 6. Deploy

1. Click **Deploy**.
2. Open **Logs** and choose the **app** container.
3. The first deploy takes a while:
   - building the image: 5 to 10 minutes;
   - installing the site into the empty database: 4 to 10 minutes more.
4. Wait for these lines, in this order:

   ```
   Installation complete.
   Shop domain set to salisberg.com (ssl=1)
   Branding v1 applied (Salisberg Hotels)
   ```

   followed by lines for the content, currency and module steps, and finally Apache starting.

During the install Coolify may show the app as "starting" or even "unhealthy" for several minutes. That is expected on the first deploy only. Do not restart it while the installer is running: wait for `Installation complete.`

Later deploys do not install anything. They start in seconds once the image is built.

## 7. Check that it worked

Open these in a browser:

| Address | Should show |
|---|---|
| `https://salisberg.com` | The homepage, with a padlock (valid certificate) |
| `http://salisberg.com` | The same page, redirected to `https://` |
| `https://salisberg.com/<ADMIN_DIR>` | The back office sign-in page |
| `https://salisberg.com/admin` | Page not found |
| `https://salisberg.com/install` | Page not found |
| `https://salisberg.com/.env.example` | Page not found |

Then:

1. Sign in to the back office with `ADMIN_EMAIL` and `ADMIN_PASSWORD`.
2. Open **Advanced Parameters › Configuration Information**. Both checks at the bottom should say **OK**.
3. In Coolify, open the **backup** container's logs. There should be a line like `database dump written: salisberg-db-….sql.gz.enc`. If it says `WARNING: BACKUP_PASSPHRASE is not set`, go back to step 5.
4. **Check the sign-in limit sees real visitors.** The site allows 10 wrong sign-ins per visitor address every 10 minutes. From a phone on mobile data, type a wrong guest password 11 times; the 11th should say "Too many attempts". Then try to sign in from a computer on Wi-Fi: it must **not** be blocked. If it is, every visitor is being seen as one address; tell the developer team before opening to guests.

## 8. First things to do in the back office

| Task | Where |
|---|---|
| Change the administrator password | Your name (top right) › My preferences |
| Give the hotel's manager the **Hotel Manager** profile, and each front desk employee **Hotel Staff** | Administration › Employees. Keep **SuperAdmin** for the developer team only. |
| Enter the Mobile Money number and the bank account | Modules and Services › Salisberg Pay › Configure. Until then guests are offered cash only. |
| Enter the hotel's real phone, address and details | Hotel Reservation System › Manage Hotel |
| Enter the real room types, photos and prices | Catalog › Manage Room Types |
| Set up outgoing email (SMTP) | Advanced Parameters › E-mail. Until then no confirmation emails are sent. |
| Hide the Channel Manager menu if it is not used | Guides › Admin Guide, section 15 |

The **Guides** menu in the back office explains each of these step by step.

## 9. Updating the site

The live site is rebuilt from the `salisberg-production` branch each time it is deployed.

1. Changes are made and tested on `develop`, then on `staging` (see `COOLIFY-STAGING.md`).
2. When they are ready: merge into `salisberg-production` and push.
3. In Coolify click **Redeploy** (or switch on **Auto Deploy** under Configuration › Advanced, and a push deploys by itself).
4. Watch the **app** log. Steps that have new work to do log what they changed, for example `Modules v11 applied`.

**What survives a redeploy:** the database, the settings file with the encryption keys, every uploaded picture, and the backups. They are on volumes.

**What does not:** anything installed or edited through the back office that lives in the code: a module or theme uploaded through the Modules page, or a file changed with a file manager. Add those to the repository instead.

**Going back to the previous version:** Coolify keeps earlier builds. Open **Deployments**, find the last good one and choose **Rollback**. The database is not rolled back; that is rarely needed, and if it is, see `BACKUP.md`.

## 10. Changing the domain later

The site's address follows `PUBLIC_URL`. To move from a temporary address to the real one:

1. Point the new name's DNS at the server (section 2).
2. In Coolify, change **Domains for app** to the new address.
3. Change `PUBLIC_URL` to the same address.
4. Redeploy. The log line `Shop domain set to <new name> (ssl=1)` confirms it.

No database edits, no reinstall.

## 11. What must never be done on the live server

| Never | Because |
|---|---|
| Delete the resource's volumes, or tick "delete volumes" when removing the resource | `db_data` is the database. `app_data` holds the encryption keys: without them every stored password stops working. |
| Delete `/data/settings.inc.php` | The site would reinstall itself on the next start and **wipe the database**. |
| Change `MYSQL_PASSWORD` or `MYSQL_ROOT_PASSWORD` in Coolify alone | The database only reads them when it is first created. The app would be locked out. The correct steps are in `AGENTS.md` ("Rotating the database passwords"). |
| Set `ADMIN_DIR` to `admin`, or change it casually | `admin` is refused by the platform. Changing it changes the back office address for everyone. |
| Set `MYSQL_VERSION=8.4` without a tested backup | One-way. See `AGENTS.md`. |
| Install the "1-Click Upgrade" module | It overwrites files that are replaced again on the next deploy, leaving the database and the code out of step. |
| Mount a volume over the code, add `ports:` to `docker-compose.yml`, or put a second proxy in front | Each breaks the deployment. See `AGENTS.md`, rules 1 to 3. |

Before any risky work on the server, take a backup by hand first (`BACKUP.md`, section 3).

## 12. When something goes wrong

| What you see | Cause and what to do |
|---|---|
| Deploy fails at once with `required variable … is missing a value` | A variable from "Must be set" is missing or misspelt. Add it and deploy again. |
| Build fails with "no Dockerfile" or tries to detect a language | The Build Pack is not **Docker Compose**. Fix it in Configuration › General. |
| App log: `… contains a quote, backslash, backtick or dollar sign` | A password or name contains one of those characters. Change the value and deploy again. |
| App shows "unhealthy" during the first deploy | The installer is still running. Wait for `Installation complete.` (up to 15 minutes). |
| App log: cannot connect to the database | On a first deploy: wait, the database is still starting. On a later deploy: the database password was changed in Coolify alone (see section 11). |
| "Bad Gateway" or "no available server" in the browser | The app container is not running yet, or the domain was put on the wrong service. It belongs on **app**. |
| Browser warns the certificate is not valid | DNS did not point at the server when the deploy ran, or the domain was written with `http://`. Fix, then redeploy. |
| The site loads but every link goes to another address | `PUBLIC_URL` does not match the domain. Correct it and redeploy. |
| "Forbidden" on pictures | Should not happen: the app repairs ownership on every start. Restart the app; if it stays, send the app log to the developer team. |
| "Too many attempts" at sign-in | The sign-in limit. Wait 10 minutes. See the Staff Guide, section 11. |
| Back office address gives "page not found" | You are using the wrong `ADMIN_DIR`. Check the value in Coolify. |
| Pictures uploaded yesterday are gone after a deploy | A module is saving pictures inside its own folder. Tell the developer team (`AGENTS.md`, rule 20). |

To read logs: Coolify › the resource › **Logs** › choose **app**, **db** or **backup**.
To run a command inside a container: Coolify › the resource › **Terminal** › choose the container.
