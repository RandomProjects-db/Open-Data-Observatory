# Deployment Runbook: Staging to Production

Single Ubuntu 24.04 server running Nginx, PHP 8.3-FPM and MariaDB. Code lives in GitHub. Configuration is exported to `config/sync`.

| Environment | Path | URL | Database |
|---|---|---|---|
| Staging | `/var/www/odo-staging` | https://staging.observatory.raiuz.site | separate staging DB |
| Production | `/var/www/odo` | https://observatory.raiuz.site | separate production DB |

Always deploy to staging first and verify before touching production. Run drush from the project root (for example `cd /var/www/odo && vendor/bin/drush ...`).

## 1. Pre-deploy

1. Confirm the change is merged, CI is green, and staging has been tested.
2. Check the working tree is clean and note the current commit:
   ```
   cd /var/www/odo
   git status --short
   git rev-parse --short HEAD
   ```
3. Tag the current state:
   ```
   git tag pre-deploy-YYYYMMDD-HHMM
   ```
   Example: `pre-deploy-20261114-2130`. Use the real date and time.
4. Back up the database:
   ```
   mkdir -p ~/backups
   vendor/bin/drush sql:dump --gzip --result-file=$HOME/backups/odo-pre-deploy-YYYYMMDD-HHMM.sql
   ls -lh ~/backups/odo-pre-deploy-YYYYMMDD-HHMM.sql.gz
   ```
   Check the file exists and is not empty before continuing. These pre-deploy dumps are separate from the nightly backups in `/var/backups/odo` (see `docs/operations.md`).

## 2. Deploy

```
cd /var/www/odo
git pull
composer install --no-dev
vendor/bin/drush updatedb -y
vendor/bin/drush config:import -y
vendor/bin/drush cache:rebuild
```

Order matters: database updates run before configuration import. If any command fails, stop and go to Rollback.

## 3. Post-deploy checks

1. Pages return HTTP 200:
   ```
   for p in / /reports /datasets /news /user/login; do
     printf '%s ' "$p"
     curl -s -o /dev/null -w '%{http_code}\n' "https://observatory.raiuz.site$p"
   done
   ```
2. Site status looks healthy: `vendor/bin/drush status`
3. No new errors in the log: `vendor/bin/drush watchdog:show --severity=Error --count=20`
4. Confirm config is in sync: `vendor/bin/drush config:status` (expect no differences).
5. Record the result in the Change log below.

## 4. Rollback

Use when post-deploy checks fail and a quick fix is not available.

1. Put the site in maintenance mode: `vendor/bin/drush state:set system.maintenance_mode 1 --input-format=integer`
2. Return the code to the tag:
   ```
   git checkout pre-deploy-YYYYMMDD-HHMM
   composer install --no-dev
   ```
3. Restore the database dump:
   ```
   vendor/bin/drush sql:drop -y
   gunzip -c ~/backups/odo-pre-deploy-YYYYMMDD-HHMM.sql.gz | vendor/bin/drush sql:cli
   ```
4. Rebuild caches: `vendor/bin/drush cache:rebuild`
5. Turn off maintenance mode: `vendor/bin/drush state:set system.maintenance_mode 0 --input-format=integer`
6. Re-run the post-deploy checks.
7. Record the rollback in the Change log. Return to a branch (`git checkout main`) only when the next fix is ready to deploy.

## Security update procedure

1. On staging, list outdated Drupal packages:
   ```
   composer outdated 'drupal/*'
   ```
2. Update on staging first (for example `composer update drupal/core-recommended --with-all-dependencies`), then run `drush updatedb`, `drush config:export` if config changed, and `drush cache:rebuild`.
3. Test on staging: key pages, login, one report, one dataset, and the importer (`drush odo:import-worldbank`) if the update touches core or contributed modules it relies on.
4. Commit the updated `composer.json`, `composer.lock` and any config. Merge through the normal pull request flow.
5. Deploy to production using sections 1 to 3 above.
6. Record the update in the Change log.

## Change log

| Date | Change | Environment | Result | Rollback needed |
|---|---|---|---|---|
| | | | | |
