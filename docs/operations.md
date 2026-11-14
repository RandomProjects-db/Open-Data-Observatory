# Operations

Day-to-day running of https://observatory.raiuz.site.

## Scheduled jobs

| Job | Schedule | Where |
|---|---|---|
| Database and files backup | Daily 02:30 (server time, UTC) | `/etc/cron.d/odo-backup` runs `scripts/backup.sh` |
| Drupal cron (automated cron) | Every 3 hours on page requests | `automated_cron.settings` |
| Lightsail disk snapshot | Daily | Lightsail console |
| TLS certificate renewal | Twice daily check | `certbot.timer` (certificate valid to 12 Feb 2027) |
| Ubuntu security updates | Daily | `unattended-upgrades` (enabled) |

## Backups

`scripts/backup.sh` writes to `/var/backups/odo` (mode 700):
- `db-YYYYMMDD-HHMM.sql.gz` (Drush SQL dump, about 850 KB)
- `files-YYYYMMDD-HHMM.tar.gz` (uploaded files)
- `backup.log` (one line per successful run)

Kept for 7 days. Lightsail snapshots cover whole-server recovery.

Pre-deploy dumps (`~/backups`, see the runbook) are separate and kept until the deploy is confirmed.

### Restore test

Done 14 Nov 2026: restored the latest dump into a scratch database `odo_restore_test`;
50 of 50 nodes present; scratch database dropped. Repeat monthly.

### Restore production

```bash
cd /var/www/odo
vendor/bin/drush sql:dump --gzip --result-file=/tmp/before-restore.sql   # safety copy
gunzip -c /var/backups/odo/db-<STAMP>.sql.gz | vendor/bin/drush sql:cli
tar -xzf /var/backups/odo/files-<STAMP>.tar.gz -C web/sites/default
sudo chgrp -R www-data web/sites/default/files
vendor/bin/drush cache:rebuild
scripts/smoke-test.sh
```

## Smoke test

```bash
scripts/smoke-test.sh                                   # production
scripts/smoke-test.sh https://staging.observatory.raiuz.site
```

Checks that `/`, `/reports`, `/datasets`, `/news`, `/search?keys=Lebanon`, `/user/login` and
`/sitemap.xml` return 200. Exit code 1 on any failure. Run after every deploy, permission
change or module change.

## Health checks

```bash
vendor/bin/drush status
vendor/bin/drush core:requirements --severity=2          # errors only
vendor/bin/drush watchdog:show --severity=Error --count=20
vendor/bin/drush config:status                           # expect no differences
composer audit                                           # known vulnerabilities
sudo tail -50 /var/log/nginx/error.log
```

## Refreshing data

```bash
vendor/bin/drush odo:import-worldbank
vendor/bin/drush php:script scripts/content.php          # regenerate reports
vendor/bin/drush simple-sitemap:generate
```

## Performance and SEO checks

```bash
scripts/lighthouse.sh <label>        # writes docs/reports/lighthouse/<label>/summary.txt
```

## Uptime monitoring

Not set up yet: needs an external monitor (UptimeRobot) because a check running on this
server cannot report that the server is down. See `REMAINING.md`.

## Incidents so far

| Date | Impact | Cause | Fix |
|---|---|---|---|
| 14 Nov 2026 | Homepage 404 for a few minutes after install | Files created with `umask 077` | Permissions corrected |
| 14 Nov 2026 | All pages 500 for about 20 minutes | Module files synced with wrong group | `chgrp www-data`, setgid, smoke test added (issue #1) |
| 14 Nov 2026 | Nginx reload rejected; site stayed up | `http2 on;` unsupported in Nginx 1.24 | `listen ... http2` syntax |
