# Salisberg backups and recovery

What is backed up, where it is kept, how to get copies off the server, and how to bring the site back if the server is lost.

**The short version.** The site backs itself up every night, but those backups sit on the same server as the site. If the server dies, they die with it. Until section 4 is done, a server crash means losing every booking.

## Contents

1. [What you must keep safe, away from the server](#1-what-you-must-keep-safe-away-from-the-server)
2. [What is backed up, and what is not](#2-what-is-backed-up-and-what-is-not)
3. [Looking at the backups, and taking one by hand](#3-looking-at-the-backups-and-taking-one-by-hand)
4. [Getting copies off the server](#4-getting-copies-off-the-server)
5. [Proving a backup works](#5-proving-a-backup-works)
6. [Putting back one thing on a working site](#6-putting-back-one-thing-on-a-working-site)
7. [The server is gone: full recovery](#7-the-server-is-gone-full-recovery)
8. [Routine](#8-routine)

Commands below are run on the server over SSH unless it says otherwise. Container names in Coolify include a random part; find them with:

```bash
docker ps --format '{{.Names}}' | grep -E '^(app|db|backup)-'
```

If a staging copy runs on the same server there are two of each. `docker inspect <app name> --format '{{range .Config.Env}}{{println .}}{{end}}' | grep PUBLIC_URL` shows which is which; containers with the same random part belong together.

## 1. What you must keep safe, away from the server

Keep these in a password manager, or printed in a locked drawer. Not only on the server, and not in the repository.

| Item | Why |
|---|---|
| **`BACKUP_PASSPHRASE`** | The backups are encrypted with it. **No passphrase, no restore.** There is no way round it. |
| The full list of Coolify environment variables for the site | Needed to rebuild the site on a new server (`COOLIFY.md`, section 5). |
| Sign-in for the server provider, the domain registrar and GitHub | To create a new server, repoint the domain and reach the code. |
| Sign-in for wherever the off-server copies are stored | To get them back. |

The code itself is safe on GitHub and needs no backup.

## 2. What is backed up, and what is not

The `backup` container runs once on its first start and then every night at 02:00 UTC (`BACKUP_HOUR_UTC`). Each run writes two files into the `db_backups` volume and removes files older than 14 days (`BACKUP_KEEP_DAYS`).

| File | Contains |
|---|---|
| `salisberg-db-<date>-<time>.sql.gz.enc` | The whole database: bookings, guests, payments recorded, rooms, prices, settings, staff accounts. |
| `salisberg-data-<date>-<time>.tar.gz.enc` | `/data`: the settings file with the **encryption keys**, and the pictures of the homepage gallery, amenities and footer. |

Both are encrypted with AES-256.

**Not in the nightly backup:**

| What | Where it lives | If lost |
|---|---|---|
| Room and hotel photographs, logos | `app_img` volume (`/var/www/html/img`) | The site works but shows no room photos until they are uploaded again. |
| Files attached by guests or staff (ID documents on bookings, attachments) | `app_upload`, `app_download` volumes | Gone for good. |

Section 4 covers these too.

## 3. Looking at the backups, and taking one by hand

**Is it working?** In Coolify open the site's **Logs** and choose **backup**. Each night should add:

```
[backup] 2026-10-07T02:00:11Z database dump written: salisberg-db-20261007-020003.sql.gz.enc (412345 bytes)
[backup] 2026-10-07T02:00:12Z data archive written: salisberg-data-20261007-020003.tar.gz.enc
```

- `WARNING: BACKUP_PASSPHRASE is not set` means the backups are **not encrypted**. Set the variable in Coolify and redeploy.
- `ERROR: database dump failed` means last night has no backup. Check that the database is running; it retries every 10 minutes.

**List them:**

```bash
docker exec <backup container> ls -lh /backups
```

**Take one now**, for example before a risky change. The container makes a backup whenever today's is missing, so:

```bash
docker exec <backup container> rm /backups/.last-success
docker restart <backup container>
docker logs --tail 5 <backup container>
```

This works at any hour: on start the container takes a backup straight away when that marker file is absent.

## 4. Getting copies off the server

Pick one of the two ways. Way A needs nothing but your computer. Way B is automatic and is the one to aim for.

### Way A: pull copies to your own computer (simple, by hand)

On the server, once, find where the backup volume is on disk:

```bash
docker inspect <backup container> --format '{{range .Mounts}}{{.Destination}} {{.Source}}{{println}}{{end}}'
```

The line starting `/backups` gives the folder, something like `/var/lib/docker/volumes/<long name>_db-backups/_data`.

Then, **on your own computer**, whenever you want a copy:

```bash
scp "root@<server address>:/var/lib/docker/volumes/<long name>_db-backups/_data/salisberg-*" ./salisberg-backups/
```

The files stay encrypted, so the folder can also be synced to Google Drive, OneDrive or a USB disk. Do it at least weekly, and always before an upgrade.

Weakness: it only happens when someone remembers. A booking taken after the last pull is lost with the server.

### Way B: automatic nightly copy to cloud storage (recommended)

A small script on the server sends the night's files to storage somewhere else, using [rclone](https://rclone.org). Any provider rclone supports will do: Backblaze B2, Cloudflare R2, Wasabi, Amazon S3, Google Drive. A few gigabytes cost little or nothing.

> **This script has not been run on the live server yet.** Set it up, then confirm with the last step that files really arrive.

**1. Install rclone and connect it to the storage** (answers depend on the provider; follow rclone's page for it). Name the connection `offsite`.

```bash
apt-get update && apt-get install -y rclone
rclone config
rclone mkdir offsite:salisberg-backups
```

**2. Save the passphrase for the picture archives** (the same value as `BACKUP_PASSPHRASE`), readable by root only:

```bash
umask 077
nano /root/.salisberg-backup-pass      # paste the passphrase on one line, save
```

**3. Find the four volume names** for the production site:

```bash
docker inspect <app container>    --format '{{range .Mounts}}{{.Destination}} {{.Name}}{{println}}{{end}}'
docker inspect <backup container> --format '{{range .Mounts}}{{.Destination}} {{.Name}}{{println}}{{end}}'
```

Note the names beside `/backups`, `/var/www/html/img`, `/var/www/html/upload` and `/var/www/html/download`.

**4. Create the script** `/root/salisberg-offsite.sh`, putting the four names in at the top:

```bash
#!/bin/bash
# Sends the Salisberg backups and picture volumes to off-server storage.
set -uo pipefail

VOL_BACKUPS=xxxx_db-backups
VOL_IMG=xxxx_app-img
VOL_UPLOAD=xxxx_app-upload
VOL_DOWNLOAD=xxxx_app-download
REMOTE=offsite:salisberg-backups
PASS=/root/.salisberg-backup-pass
WORK=/root/salisberg-offsite
ROOT=/var/lib/docker/volumes

log() { echo "[offsite] $(date -u +%Y-%m-%dT%H:%M:%SZ) $*"; }
mkdir -p "$WORK"

# 1. the nightly database and settings files (already encrypted by the site)
rclone copy "$ROOT/$VOL_BACKUPS/_data" "$REMOTE/nightly" --include 'salisberg-*' \
    && log "nightly files sent" || log "ERROR: nightly files not sent"

# 2. pictures and uploads: one encrypted archive each, replaced every night
for pair in "img:$VOL_IMG" "upload:$VOL_UPLOAD" "download:$VOL_DOWNLOAD"; do
    name="${pair%%:*}"; vol="${pair#*:}"
    out="$WORK/salisberg-$name.tar.gz.enc"
    if tar -C "$ROOT/$vol/_data" -czf - . \
        | openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt -pass "file:$PASS" -out "$out"; then
        rclone copy "$out" "$REMOTE/files" && log "$name sent" || log "ERROR: $name not sent"
    else
        log "ERROR: could not archive $name"
    fi
done

# 3. keep 60 days of nightly files in the storage
rclone delete "$REMOTE/nightly" --min-age 60d
```

```bash
chmod 700 /root/salisberg-offsite.sh
```

**5. Run it once by hand and look at the result:**

```bash
/root/salisberg-offsite.sh
rclone ls offsite:salisberg-backups
```

You should see the `salisberg-db-…` and `salisberg-data-…` files under `nightly/` and three archives under `files/`.

**6. Schedule it** for 03:00 UTC, an hour after the site's own backup:

```bash
( crontab -l 2>/dev/null; echo '0 3 * * * /root/salisberg-offsite.sh >> /var/log/salisberg-offsite.log 2>&1' ) | crontab -
```

Check `/var/log/salisberg-offsite.log` the next morning, and from time to time after that.

## 5. Proving a backup works

A backup that has never been restored is a hope, not a backup. Do this once now, and again every few months.

**Quick check (two minutes, harmless).** Confirms the file opens with your passphrase and is not damaged:

```bash
docker exec -it <backup container> bash
f=$(ls -1 /backups/salisberg-db-*.enc | tail -1)
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass env:BACKUP_PASSPHRASE -in "$f" | gzip -t && echo OK
exit
```

`OK` is a pass. `bad decrypt` means the passphrase in Coolify is not the one the file was made with.

**Real check.** Restore last night's files into the staging copy and look at the bookings there: `COOLIFY-STAGING.md`, section 8. That exercises every step of a real recovery without touching the live site. Use a file fetched **from the off-server storage**, not from the server, so the whole chain is proven.

## 6. Putting back one thing on a working site

For when the server is fine but data was damaged: a mistaken bulk delete, a bad import.

**Think first.** Restoring the database returns the whole site to the moment of that backup. Every booking and payment recorded since then disappears. If only a few records are wrong, it is usually better to restore into staging, look up the right values there, and correct the live site by hand.

To restore the live database:

1. Take a fresh backup of the present state (section 3), in case the restore makes things worse.
2. In the back office switch on maintenance mode (**Preferences › Maintenance**, Enable Shop: No) so no bookings arrive meanwhile.
3. Restore, inside the **backup** container:

   ```bash
   docker exec -it <backup container> bash
   export MYSQL_PWD="$DB_PASSWORD"
   f=/backups/salisberg-db-YYYYMMDD-HHMMSS.sql.gz.enc
   openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass env:BACKUP_PASSPHRASE -in "$f" | gzip -t && echo OK
   openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass env:BACKUP_PASSPHRASE -in "$f" | gunzip | mysql -h"$DB_HOST" -u"$DB_USER" "$DB_NAME"
   exit
   ```

4. Restart the app in Coolify, check the Bookings list, switch maintenance mode off.

## 7. The server is gone: full recovery

You need: the off-server copies (section 4), the passphrase and the variable list (section 1). Allow one to two hours. Work through it in order.

### Step 1: a new server with the site freshly installed

1. Create a new server and install Coolify on it.
2. Point the DNS records for `salisberg.com` and `www` at the new server's address.
3. Follow `COOLIFY.md`, sections 3 to 6, using **exactly the same environment variable values as before**, in particular `MYSQL_PASSWORD`, `ADMIN_DIR` and `BACKUP_PASSPHRASE`.
4. Wait for `Installation complete.` The site is now up, but empty: a sample hotel and no bookings.

### Step 2: bring the backup files to the new server

Choose the newest `salisberg-db-…` and the `salisberg-data-…` with the same date and time.

From cloud storage (Way B), on the new server:

```bash
apt-get update && apt-get install -y rclone && rclone config     # connect to the same storage, name it offsite
mkdir -p /root/restore && cd /root/restore
rclone ls offsite:salisberg-backups/nightly | tail
rclone copy offsite:salisberg-backups/nightly /root/restore --include 'salisberg-*-20261007-020003*'
rclone copy offsite:salisberg-backups/files /root/restore
```

From your own computer (Way A), on that computer:

```bash
scp ./salisberg-backups/salisberg-*-20261007-020003* root@<new server>:/root/restore/
```

### Step 3: restore the database and the settings

```bash
cd /root/restore
APP=<app container>
docker cp salisberg-db-20261007-020003.sql.gz.enc   "$APP:/tmp/"
docker cp salisberg-data-20261007-020003.tar.gz.enc "$APP:/tmp/"
docker exec -it "$APP" bash
```

Inside the container (each `openssl` line asks for the passphrase):

```bash
cd /tmp
export MYSQL_PWD="$DB_PASSWORD"
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -in salisberg-db-*.sql.gz.enc | gzip -t && echo OK
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -in salisberg-db-*.sql.gz.enc | gunzip | mysql -h"$DB_HOST" -u"$DB_USER" "$DB_NAME"
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -in salisberg-data-*.tar.gz.enc | tar -xzf - -C /
chown -R www-data:www-data /data
rm /tmp/salisberg-*.enc
exit
```

The second archive puts back the original settings file, and with it the encryption keys that the restored accounts and sessions were made with. **Do not skip it.**

If the new server was given a different `MYSQL_PASSWORD` from the old one, the restored settings file now holds the wrong database password. `COOLIFY-STAGING.md`, section 8, step 4, has the one command that corrects it.

### Step 4: restore the pictures and uploads (if section 4, Way B, was in use)

```bash
cd /root/restore
for name in img upload download; do
  docker cp "salisberg-$name.tar.gz.enc" "$APP:/tmp/"
  docker exec -it "$APP" sh -c "openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -in /tmp/salisberg-$name.tar.gz.enc | tar -xzf - -C /var/www/html/$name && chown -R www-data:www-data /var/www/html/$name"
done
docker exec "$APP" sh -c 'rm /tmp/salisberg-*.enc'
```

Without these archives, upload the room photographs again through the back office.

### Step 5: restart and check

1. In Coolify, **Restart** the site.
2. Sign in to the back office with the **old** accounts and passwords.
3. Check, in this order: the Bookings list shows the real bookings up to the backup's date; a guest can sign in on the website; room photographs show; **Advanced Parameters › Configuration Information** says OK twice; the **backup** log shows a new dump written.
4. Remove the copies from the server: `rm -r /root/restore`.
5. Set up section 4 again on the new server. It was on the old one.

### What will be missing

Everything that happened between the last off-server copy and the crash. With a nightly copy, that is at most one day of bookings. Ask the front desk for bookings and payments they remember from that day and enter them again with **Book Now**.

## 8. Routine

| When | What |
|---|---|
| Once, now | Section 1 (passphrase and variables stored safely). Section 4. Section 5, both checks. |
| Every week | Glance at the **backup** log in Coolify, and at `/var/log/salisberg-offsite.log` if Way B is used. With Way A, pull a copy. |
| Every three months | Section 5, the real check, from an off-server file. |
| Before any upgrade or risky change | A backup by hand (section 3) and a copy off the server. |
| When the passphrase changes | Old files still need the **old** passphrase. Keep both until the old files have expired. |
