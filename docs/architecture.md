# Architecture

## Stack

| Layer | Choice | Version |
|---|---|---|
| CMS | Drupal (recommended-project template) | 11.4.12 |
| CLI | Drush | 13.8.1 |
| Language | PHP-FPM | 8.3.6 |
| Web server | Nginx, HTTP/2, TLS from Let's Encrypt | 1.24.0 |
| Database | MariaDB | 10.11 |
| Host | AWS Lightsail, Ubuntu 24.04 LTS, 2 GB RAM, 2 vCPU, 60 GB SSD, Frankfurt | |
| DNS | Namecheap (`raiuz.site`), A record `observatory` | |
| Code | GitHub `RandomProjects-db/Open-Data-Observatory` | |

Contributed modules: Pathauto 1.16, Token 1.18, Metatag 2.2.7 (with Views, Open Graph,
Twitter Cards, Verification), Schema.org Metatag 3.1, Simple XML Sitemap 4.2.3, Redirect 1.13.

## Environments

| Environment | Path | URL | Database | Git branch |
|---|---|---|---|---|
| Production | `/var/www/odo` | https://observatory.raiuz.site | `odo` (user `odo`) | `main` |
| Staging | `/var/www/odo-staging` | https://staging.observatory.raiuz.site (pending DNS) | `odo_staging` (user `odo_staging`) | `staging` |

Both run on the same server. Each database user can only access its own database.

## Request flow

```
Browser --HTTPS/HTTP2--> Nginx (443) --FastCGI--> PHP-FPM 8.3 --> Drupal --> MariaDB
                          |
                          +-- static files served directly (30-day cache headers)
                          +-- blocked: dotfiles, settings*.php, composer.*, *.yml, private files
```

Drupal's page cache serves anonymous visitors for up to 15 minutes. Cache tags clear
cached pages when content changes, so edits appear immediately.

## Data flow

```
World Bank API --(drush odo:import-worldbank)--> Dataset nodes (40)
                                                    |
                         scripts/content.php -------+--> Report nodes (country comparisons)
                                                    |
                         Views, search, sitemap, charts <-- read Dataset and Report nodes
```

- The importer is idempotent: it updates existing datasets (matched on indicator and country codes).
- Reports are regenerated from dataset values, so they stay consistent with the data.

## Repository layout

```
config/sync/            Exported Drupal configuration (source of truth for site structure)
web/modules/custom/     odo_core module
web/themes/custom/      odo_theme
scripts/                Idempotent build scripts, smoke test, audit, backup
assets/                 Files appended to scaffolded core files (robots.txt)
docs/                   Documentation
.github/workflows/      CI (PHPCS, PHPUnit, settings.php guard)
```

Not in Git: `vendor/`, `web/core/`, contrib modules (installed by Composer from `composer.lock`),
`settings.php`, uploaded files, private files.
