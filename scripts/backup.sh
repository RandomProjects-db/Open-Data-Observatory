#!/usr/bin/env bash
# Nightly database and files backup with 7-day retention.
# Installed as /etc/cron.d/odo-backup (runs as ubuntu at 02:30 server time).
set -euo pipefail
SITE=/var/www/odo
DEST=/var/backups/odo
STAMP=$(date +%Y%m%d-%H%M)
mkdir -p "$DEST"
cd "$SITE"
vendor/bin/drush sql:dump --gzip --result-file="$DEST/db-$STAMP.sql" --quiet
tar -czf "$DEST/files-$STAMP.tar.gz" -C "$SITE/web/sites/default" files
# Keep 7 days. Lightsail automatic snapshots cover whole-disk recovery.
find "$DEST" -name 'db-*.sql.gz' -mtime +7 -delete
find "$DEST" -name 'files-*.tar.gz' -mtime +7 -delete
echo "$(date -Is) backup ok: db-$STAMP.sql.gz files-$STAMP.tar.gz" >> "$DEST/backup.log"
