# A staging copy of Salisberg on Coolify

Staging is a second, private copy of the site where changes are tried before guests see them. It is also the place to practise the two things that cannot be practised on the live site: restoring a backup, and the MySQL 8.4 upgrade.

Read [`COOLIFY.md`](COOLIFY.md) first. This guide only describes what is different.

## Contents

1. [How staging fits in](#1-how-staging-fits-in)
2. [Before you start](#2-before-you-start)
3. [Create the staging resource](#3-create-the-staging-resource)
4. [Environment variables](#4-environment-variables)
5. [Deploy and check](#5-deploy-and-check)
6. [Keep it private](#6-keep-it-private)
7. [Day to day: testing a change](#7-day-to-day-testing-a-change)
8. [Fill staging with a copy of the live data](#8-fill-staging-with-a-copy-of-the-live-data)
9. [Rehearsing the MySQL 8.4 upgrade](#9-rehearsing-the-mysql-84-upgrade)
10. [Starting staging again from nothing](#10-starting-staging-again-from-nothing)

## 1. How staging fits in

| Branch | What it is | Where it runs |
|---|---|---|
| `develop` | Work in progress | The developer's own computer |
| `staging` | What is being tried next | `https://staging.salisberg.com` |
| `salisberg-production` | What guests use | `https://salisberg.com` |

A change moves left to right: `develop` → `staging` → `salisberg-production`. Nothing goes to production that has not run on staging.

Staging and production are **two separate resources** in Coolify. Each has its own containers, its own database and its own volumes. They share the server and nothing else, so nothing done on staging can touch a guest's booking.

## 2. Before you start

- **Memory.** Staging is a second full copy (app, database, backup). On a server with 2 GB of memory, running both is tight. 4 GB is comfortable. If the server is small, stop staging in Coolify when it is not in use (**Stop** keeps its data).
- **DNS.** Add one record where the domain is managed:

  | Type | Name | Value |
  |---|---|---|
  | A | `staging` | The server's IP address |

- **The branch.** `staging` must exist on GitHub: `git push -u origin staging`.

## 3. Create the staging resource

1. In Coolify open the **Salisberg** project. Add an environment named **staging** (the environment menu at the top of the project), and open it. Keeping it in its own environment makes it hard to click the wrong one.
2. **+ New** › **Public Repository**.
3. Repository URL: `https://github.com/gomathie/stayflow-hms`
4. Branch: **`staging`**
5. Build Pack: **Docker Compose**. Base Directory `/`. Docker Compose Location `/docker-compose.yml`.
6. On **Configuration › General**:
   - **Domains for app:** `https://staging.salisberg.com`
   - **Domains for db** and **backup:** empty.
   - Give the resource a name that cannot be mistaken, for example `salisberg-STAGING`.
7. Save.

## 4. Environment variables

The same list as production (`COOLIFY.md`, section 5), with **different values**. Do not copy production's passwords.

| Variable | Staging value |
|---|---|
| `PUBLIC_URL` | `https://staging.salisberg.com` |
| `MYSQL_PASSWORD` | A new random password, not production's |
| `MYSQL_ROOT_PASSWORD` | Another new random password |
| `ADMIN_EMAIL` | Your own address |
| `ADMIN_PASSWORD` | A new password |
| `ADMIN_DIR` | For example `desk-staging-4m9x` |
| `BACKUP_PASSPHRASE` | A new passphrase |
| `BACKUP_KEEP_DAYS` | `3` (staging backups only use up disk) |
| `SHOP_NAME` | `Salisberg Hotels STAGING` (so the browser tab tells you where you are) |
| `SHOP_COUNTRY` | `gh` |
| `TIMEZONE` | `Africa/Accra` |

Leave `MYSQL_DATABASE` and `MYSQL_USER` out, so they are `salisberg` on both. Section 8 relies on that.

Untick **Build Variable** on every one, as on production.

## 5. Deploy and check

1. **Deploy**, and watch the **app** log for `Installation complete.` and `Shop domain set to staging.salisberg.com (ssl=1)`.
2. Run the checks in `COOLIFY.md`, section 7, against the staging address.
3. Optional, from a computer with the repository and `bash`: the automatic check.

   ```bash
   bash docker/smoke-test.sh https://staging.salisberg.com
   ```

   The website page checks and the "private files are not served" checks work against any address. The back office checks sign in with the `ADMIN_DIR`, `ADMIN_EMAIL` and `ADMIN_PASSWORD` found in the `.env` file on that computer, so they are reported as failed unless those match staging's; and the log check reads a local Docker stack only. Run it before maintenance mode is switched on (section 6), or from an address that maintenance mode lets through.

At this point staging is a fresh install with the sample hotel, not a copy of the live site. That is enough for testing code. For a copy of the real data, see section 8.

## 6. Keep it private

Staging must not be found by guests or by search engines, and must never email a real guest.

1. **Close it to the public.** In the staging back office: **Preferences › Maintenance**. Set **Enable Shop** to **No**, click **Add my IP** so you can still see the site, and Save. Everyone else gets the maintenance page, which search engines do not index. Add the address of anyone else who needs to test.
2. **Stop outgoing email.** **Advanced Parameters › E-mail** › **Never send e-mails**. Do this again every time live data is copied in (section 8), because the copy brings the live email settings with it.
3. **Do not enter the real Mobile Money number or bank account** on a fresh staging install. Use obviously false ones (`0240000000`, "TEST BANK").
4. Staging has its own back office address and its own passwords. Do not share them outside the developer team.

## 7. Day to day: testing a change

1. Finish and test the change on `develop`.
2. Move it to staging:

   ```bash
   git checkout staging
   git merge develop
   git push origin staging
   git checkout develop
   ```

3. In Coolify, on the **staging** resource, click **Redeploy** (or switch on **Auto Deploy** for staging; it is safe there).
4. Read the **app** log for the deploy steps and for PHP errors.
5. Try the change in a browser, signed in as each role it affects (Hotel Staff, Hotel Manager, SuperAdmin), and make one booking through the website from start to finish.
6. Only when it is right:

   ```bash
   git checkout salisberg-production
   git merge staging
   git push origin salisberg-production
   git checkout develop
   ```

   then redeploy **production** and watch its log.

If staging shows a problem, fix it on `develop` and repeat from step 2. Production has not been touched.

## 8. Fill staging with a copy of the live data

Do this before testing anything that depends on real bookings, and at least once to prove the backups can be restored (`BACKUP.md`, section 5). **It replaces everything on staging.**

Two files are needed from the same night: `salisberg-db-….sql.gz.enc` and `salisberg-data-….tar.gz.enc`. You also need **production's** `BACKUP_PASSPHRASE`.

### Step 1: copy the two files from production to staging

Sign in to the server over SSH. Find the container names (they include a random part):

```bash
docker ps --format '{{.Names}}' | grep -E '^(app|backup)-'
```

There will be an `app-…` and a `backup-…` for each resource. To tell which is which:

```bash
docker inspect <name> --format '{{range .Config.Env}}{{println .}}{{end}}' | grep PUBLIC_URL
```

(run it on the `app-…` containers; the two with the same random part belong together).

Then, replacing the names and the date:

```bash
PROD_BACKUP=backup-xxxxxxxx      # production's backup container
STAGING_APP=app-yyyyyyyy         # staging's app container

docker exec "$PROD_BACKUP" ls -1 /backups      # choose the newest pair
STAMP=20261007-020000

docker cp "$PROD_BACKUP:/backups/salisberg-db-$STAMP.sql.gz.enc"   /root/
docker cp "$PROD_BACKUP:/backups/salisberg-data-$STAMP.tar.gz.enc" /root/
docker cp "/root/salisberg-db-$STAMP.sql.gz.enc"   "$STAGING_APP:/tmp/"
docker cp "/root/salisberg-data-$STAMP.tar.gz.enc" "$STAGING_APP:/tmp/"
rm /root/salisberg-db-$STAMP.sql.gz.enc /root/salisberg-data-$STAMP.tar.gz.enc
```

### Step 2: restore inside the staging app container

Open a shell in it. **Check twice that it is staging:**

```bash
docker exec -it "$STAGING_APP" bash
echo "$PUBLIC_URL"        # must print https://staging.salisberg.com
```

Then, inside the container:

```bash
cd /tmp
export MYSQL_PWD="$DB_PASSWORD"

# 1. check the file opens (asks for production's passphrase; prints OK)
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -in salisberg-db-*.sql.gz.enc | gzip -t && echo OK

# 2. the database (asks for the passphrase again)
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -in salisberg-db-*.sql.gz.enc | gunzip | mysql -h"$DB_HOST" -u"$DB_USER" "$DB_NAME"

# 3. the settings file, encryption keys and module pictures
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -in salisberg-data-*.tar.gz.enc | tar -xzf - -C /

# 4. the restored settings file holds production's database password; put staging's back
php -r '$f="/data/settings.inc.php";$s=file_get_contents($f);$n=preg_replace_callback("/define\(\x27_DB_PASSWD_\x27,\s*\x27[^\x27]*\x27\);/",function(){return "define(\x27_DB_PASSWD_\x27, \x27".getenv("DB_PASSWORD")."\x27);";},$s,1,$c);if($c===1){file_put_contents($f,$n);echo "database password line updated\n";}else{echo "NOT changed: line not found\n";}'

chown -R www-data:www-data /data
rm /tmp/salisberg-*.enc
exit
```

Step 4 must print `database password line updated`. If it prints `NOT changed`, open `/data/settings.inc.php` and set the `_DB_PASSWD_` line by hand to staging's `MYSQL_PASSWORD`.

### Step 3: restart and tidy up

1. In Coolify, **Restart** the staging resource. On start the app sets the address back to `staging.salisberg.com` by itself (log line `Shop domain set to staging.salisberg.com (ssl=1)`).
2. Sign in to the staging back office **with the live site's accounts and passwords**: the staging accounts were replaced by the copy.
3. Straight away, repeat section 6: **maintenance mode on, e-mail off.** Until this is done, staging can email real guests.
4. Check the Bookings list shows the live bookings.

### What the copy does not include

- **Room and hotel photographs** (`img`), and files uploaded by guests or staff. Staging will show its own sample photos, or gaps where a live room has no staging photo. The backups do not hold these yet; see `BACKUP.md`, section 2.
- Anything that happened on the live site after the backup was taken.

### Guest data

After this, staging holds real names, phone numbers and emails. Treat it with the same care as the live site: keep it in maintenance mode, do not hand out its passwords, and start it again from nothing (section 10) when the test is over if the copy is no longer needed.

## 9. Rehearsing the MySQL 8.4 upgrade

This is the rehearsal `AGENTS.md` asks for before the live database is upgraded. It cannot be undone, which is exactly why it is done here first.

1. Fill staging with a fresh copy of the live data (section 8) and confirm the site works.
2. On the **staging** resource, add the variable `MYSQL_VERSION=8.4` and redeploy.
3. Open the **db** log. Wait for the upgrade to finish: lines saying the data dictionary and the server upgrade have **completed**, then `ready for connections`. With a small database this takes under a minute.
4. The **app** must start without errors. Sign in, open Bookings, make a test booking through the website.
5. Check the **backup** log the next morning, or restart the backup container, for `database dump written`.
6. Write down how long step 3 took and anything unexpected.

Only after this has gone cleanly, backups are being copied off the server, and a quiet hour is chosen, is the same variable set on production. From then on staging stays on 8.4 too.

## 10. Starting staging again from nothing

When staging has drifted too far, or holds a copy of guest data that is no longer needed:

1. In Coolify, on the **staging** resource (read the name twice): **Stop**.
2. **Danger Zone › Delete**, with the option to delete volumes ticked. This is the one place where deleting volumes is right, because they are staging's own.
3. Create it again from section 3. Keeping the variables in your password manager makes this a ten-minute job.

**Never do this on the production resource.**
