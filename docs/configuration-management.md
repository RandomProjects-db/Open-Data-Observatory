# Configuration management

## Principle

Site structure (content types, fields, Views, roles, menus, SEO settings, theme settings) is
configuration, stored as YAML in `config/sync` and versioned in Git. Content (nodes, terms,
uploads) lives in the database and is backed up, not versioned.

## How configuration is created

Structure is built by idempotent PHP scripts run with Drush, then exported. Scripts make
changes repeatable and reviewable; the export makes them deployable.

| Order | Script | Creates |
|---|---|---|
| 1 | `scripts/content-model.php` | Content types, fields, vocabularies, terms |
| 2 | `scripts/views.php` | Listings with filters, Team page, site search |
| 3 | `scripts/seo.php` | Pathauto patterns, Metatag defaults, sitemap settings, editor permissions |
| 4 | `scripts/theme.php` | Default theme, blocks, front page title, performance settings |
| 5 | `drush odo:import-worldbank` | Dataset content (database, not config) |
| 6 | `scripts/content.php` | Reports, news, About page, menu links (content) |

```bash
vendor/bin/drush php:script scripts/<script>.php
vendor/bin/drush config:export -y
git add -A && git commit
```

Every script can be re-run safely: it loads existing items and updates them, or deletes and
rebuilds Views.

## Deploying configuration

On the target environment, after `git pull` and `composer install`:

```bash
vendor/bin/drush updatedb -y
vendor/bin/drush config:import -y
vendor/bin/drush cache:rebuild
vendor/bin/drush config:status   # expect: No differences
```

Full procedure, including backups and rollback: [deploy runbook](deploy-runbook.md).

## Rules

- Never change configuration on production through the admin UI without exporting and
  committing it the same day; otherwise the next `config:import` overwrites it.
- Check `drush config:status` before any import. Unexpected differences mean someone
  changed production directly.
- `settings.php` is environment-specific and never committed (CI fails if it is).

## Git workflow

- Work happens in the staging copy (`/var/www/odo-staging`). Run PHPCS, PHPUnit and a secret
  scan there, commit, and push to both `staging` and `main`.
- Production (`/var/www/odo`) only receives code through the deploy runbook (`git pull`,
  `composer install --no-dev`), so it has no test tools and no local changes.
- Commit messages follow `type: description` (feat, fix, docs, chore).
- Until 15 Nov 2026 commits were made directly on production while staging did not exist yet.
