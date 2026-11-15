# Changelog

Times are UTC. Commit hashes link to https://github.com/RandomProjects-db/Open-Data-Observatory/commit/<hash>.

## 14 Nov 2026

| Time | Commit | Change |
|---|---|---|
| 20:51 | `ec8202e` | Drupal 11.4 installed on AWS Lightsail with HTTPS; configuration exported |
| 20:57 | `7c566d1` | Content model: Report, Dataset, News, Team Member; Region and Topic vocabularies |
| 21:09 | `dee91f6` | `odo_core`: World Bank importer with validation and retries, Drush command, Latest datasets block, 9 unit tests |
| 21:36 | `8095b30` | Listings with filters; CI workflow; smoke test; runbook, triage policy, editor guide. Fixed site-wide 500 (file permissions) |
| 21:43 | `7a08747` | Pathauto URLs, Metatag, Schema.org, XML sitemap, robots.txt; site search with Views (core search unusable on 11.4); editor permissions |
| 21:55 | `e9a5906` | `odo_theme` sub-theme, accessible dataset chart; SEO and analytics docs |
| 22:02 | `e18b8cd` | Theme live; accessibility and performance fixes (Lighthouse 100 in all four categories); nightly backups with restore test; demo content |
| 22:05 | `d3e15fe` | Backlog of real issues; 12 GitHub issues, 7 closed; project board |
| 22:05 | `6adaee5` | `composer.lock` hash refreshed (found while installing staging) |
| 22:07 | `bebf404` | README |

## 15 Nov 2026

| Change |
|---|
| Full documentation set: architecture, server setup, content model, configuration management, module and theme guides, operations, security, SEO audit template, changelog, remaining work |
| Google Tag Manager with consent mode (`eadd62f`); Search Console verified (`8f91a95`); sitemap base URL fixed, 63 URLs accepted |
| End-to-end analytics check script (`d206657`) |
| Staging environment live at https://staging.observatory.raiuz.site (noindex, no analytics, own database) |
| Update rehearsal on staging (drupal/token 1.17.0 to 1.18.0); first production deploy by the runbook (`b652b37`, tag `pre-deploy-20261114-2256`); production now installs without dev tools |
| GitHub billing resolved; first green CI run (PHPCS, PHPUnit, settings.php guard); issue #11 closed |
