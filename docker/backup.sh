#!/bin/bash
# Nightly backup loop for the Salisberg stack. Runs in the "backup" service.
#
# Each run writes two files to /backups:
#   salisberg-db-<UTC timestamp>.sql.gz[.enc]    full database dump
#   salisberg-data-<UTC timestamp>.tar.gz[.enc]  /data: the settings file (encryption keys) and module pictures
# With BACKUP_PASSPHRASE set, files are AES-256 encrypted (.enc). Without it they
# are written unencrypted and a warning is logged on every run.
#
# Restore: see "Backups and restore" in AGENTS.md.
set -uo pipefail

BACKUP_DIR=/backups
KEEP_DAYS="${BACKUP_KEEP_DAYS:-14}"
HOUR="${BACKUP_HOUR_UTC:-2}"
export MYSQL_PWD="$DB_PASSWORD"

log() { echo "[backup] $(date -u +%Y-%m-%dT%H:%M:%SZ) $*"; }

# Reads stdin, writes the (optionally encrypted) file named by $1 without its .enc suffix
store() {
    if [ -n "${BACKUP_PASSPHRASE:-}" ]; then
        openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt -pass env:BACKUP_PASSPHRASE -out "$1.enc"
    else
        cat > "$1"
    fi
}

run_backup() {
    local stamp db_file data_file suffix=""
    stamp="$(date -u +%Y%m%d-%H%M%S)"
    db_file="$BACKUP_DIR/salisberg-db-$stamp.sql.gz"
    data_file="$BACKUP_DIR/salisberg-data-$stamp.tar.gz"
    [ -n "${BACKUP_PASSPHRASE:-}" ] && suffix=".enc" || log "WARNING: BACKUP_PASSPHRASE is not set; backups are NOT encrypted"

    if mysqldump --single-transaction --quick --no-tablespaces --routines --triggers \
            --default-character-set=utf8mb4 -h"$DB_HOST" -u"$DB_USER" "$DB_NAME" | gzip -9 | store "$db_file" \
        && [ "${PIPESTATUS[0]}" = "0" ]; then
        local size
        size="$(stat -c %s "$db_file$suffix" 2>/dev/null || echo 0)"
        # An empty database dump is a few hundred bytes; a real one is far larger
        if [ "$size" -lt 20000 ]; then
            log "ERROR: database dump is only $size bytes, treating it as failed"
            return 1
        fi
        log "database dump written: $(basename "$db_file$suffix") ($size bytes)"
    else
        log "ERROR: database dump failed"
        return 1
    fi

    if [ -d /data ]; then
        if tar -C / -czf - data 2>/dev/null | store "$data_file"; then
            log "data archive written: $(basename "$data_file$suffix")"
        else
            log "ERROR: data archive failed"
        fi
    fi

    # Retention
    find "$BACKUP_DIR" -maxdepth 1 -type f -name 'salisberg-*' -mtime +"$KEEP_DAYS" -print -delete | sed 's/^/[backup] expired: /'
    date -u +%Y%m%d > "$BACKUP_DIR/.last-success"
    return 0
}

mkdir -p "$BACKUP_DIR"
log "started; daily at ${HOUR}:00 UTC, keeping $KEEP_DAYS days"

# One backup straight away if there has never been one, so a new deploy is covered
if [ ! -e "$BACKUP_DIR/.last-success" ]; then
    run_backup || log "initial backup failed; will retry at the scheduled hour"
fi

while true; do
    today="$(date -u +%Y%m%d)"
    if [ "$(date -u +%-H)" -ge "$HOUR" ] && [ "$(cat "$BACKUP_DIR/.last-success" 2>/dev/null)" != "$today" ]; then
        run_backup || log "backup failed; retrying in 10 minutes"
    fi
    sleep 600
done
