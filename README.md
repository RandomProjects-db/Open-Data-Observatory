# Open Data Observatory

A Drupal 11 publications and open-data website for a **fictional** research
organization: an independent project, built and operated end to end.

**Live site:** https://observatory.raiuz.site
**Request board:** https://github.com/users/RandomProjects-db/projects/1
**Documentation:** [docs/README.md](docs/README.md)

It publishes 40 World Bank indicator datasets (8 countries, 5 indicators), data-driven
country comparison reports, news, search, and accessible charts.

## What's inside

| Area | Where to look |
|---|---|
| Drupal site building and configuration | Content types, taxonomies, Views, roles; all exported in `config/sync` and built by idempotent scripts in `scripts/` |
| PHP and custom module development | `web/modules/custom/odo_core`: importer service, Drush command, block plugin, PHPUnit tests |
| Theming, HTML, CSS, JavaScript | `web/themes/custom/odo_theme`: Olivero sub-theme, Twig template, preprocess hooks, vanilla JS SVG chart |
| Accessibility | Measured before and after: `docs/reports/accessibility-performance.md` |
| Performance | HTTP/2, page cache, Lighthouse LCP 1.7 s to 1.2 s (simulated mobile) |
| SEO | Pathauto, Metatag, Schema.org, XML sitemap: `docs/seo/` |
| Analytics | GA4 measurement plan and report template: `docs/analytics/`, `docs/reports/` |
| Git, staging and deployment | Production and staging on one server; `docs/deploy-runbook.md` |
| Operations | Nightly backups with a tested restore, smoke test, security update procedure |
| Request triage | `docs/triage-policy.md`; 12 real issues on the project board |
| Editor support | `docs/editor-guide.md`; Content editor role with limited permissions |

## Results (Lighthouse, mobile, 14 Nov 2026)

| | Performance | Accessibility | Best practices | SEO |
|---|---|---|---|---|
| Before (4 page types) | 99 | 96 to 100 | 100 | 92 to 100 |
| After | 100 | 100 | 100 | 100 |

Details, fixes and limits: `docs/reports/accessibility-performance.md`.

## Architecture

- Drupal 11.4, PHP 8.3-FPM, Nginx 1.24 (HTTP/2, Let's Encrypt), MariaDB 10.11
- AWS Lightsail (Ubuntu 24.04, 2 GB) in Frankfurt; daily Lightsail snapshots
- Contrib: Pathauto, Token, Metatag (+ Views, Open Graph, Twitter Cards, Verification),
  Schema.org Metatag, Simple XML Sitemap, Redirect
- Data: World Bank Open Data API, CC BY 4.0

## The custom module: `odo_core`

```
drush odo:import-worldbank [--countries=LBN,DEU] [--indicators=SP.POP.TOTL]
```

- `WorldBankParser` validates responses. The API returns errors with HTTP 200,
  so the payload shape is checked; null, non-numeric and out-of-range values are skipped.
- `WorldBankImporter` creates or updates one Dataset per indicator and country
  (re-runs never duplicate) and retries slow requests with backoff.
- Country and indicator options are allow-listed.
- `LatestDatasetsBlock` lists recent datasets with correct cache tags.
- 9 PHPUnit tests; PHPCS clean against Drupal and DrupalPractice standards.

## Local setup

Not yet tested from a clean checkout; the site was built on the server.

```bash
composer install
# Create web/sites/default/settings.php with your database (not committed).
vendor/bin/drush site:install --existing-config -y
vendor/bin/drush odo:import-worldbank
vendor/bin/drush php:script scripts/content.php
```

Checks:

```bash
vendor/bin/phpcs --standard=Drupal,DrupalPractice --extensions=php,module,theme,yml web/modules/custom web/themes/custom
vendor/bin/phpunit web/modules/custom/odo_core/tests/src/Unit
scripts/smoke-test.sh https://observatory.raiuz.site
```

## Scripts

| Script | Purpose |
|---|---|
| `content-model.php` | Content types, fields, taxonomies |
| `views.php` | Listings, filters, site search |
| `seo.php` | URL patterns, meta tags, sitemap, editor permissions |
| `theme.php` | Theme, blocks, performance settings |
| `content.php` | Reports generated from imported data, news, About page |
| `smoke-test.sh` | Fails if a key page does not return 200 |
| `lighthouse.sh` | Lighthouse audit with a summary of failing checks |
| `backup.sh` | Nightly database and files backup, 7-day retention |

## Honest notes

- The organization is fictional, as the About page says. The data is real.
- Lighthouse runs on the server with simulated throttling, not field data.
  Automated checks do not prove WCAG conformance; a manual keyboard and screen
  reader review is still to do.
- GitHub Actions CI runs PHPCS, PHPUnit and a settings.php guard on every push to main and staging.

Author: Fahed Daibes
