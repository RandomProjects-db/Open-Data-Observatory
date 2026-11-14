# Changelog

Times are UTC. Commit hashes link to https://github.com/RandomProjects-db/Open-Data-Observatory/commit/<hash>.

## 14 Nov 2026

| Time | Commit | Change |
|---|---|---|
| 20:51 | `ec8202e` | Drupal 11.4 installed on AWS Lightsail with HTTPS; configuration exported |
| 20:57 | `d5675e3` | Content model: Report, Dataset, News, Team Member; Region and Topic vocabularies |
| 21:09 | `d93b03b` | `odo_core`: World Bank importer with validation and retries, Drush command, Latest datasets block, 9 unit tests |
| 21:36 | `1445f98` | Listings with filters; CI workflow; smoke test; runbook, triage policy, editor guide. Fixed site-wide 500 (file permissions) |
| 21:43 | `7cec251` | Pathauto URLs, Metatag, Schema.org, XML sitemap, robots.txt; site search with Views (core search unusable on 11.4); editor permissions |
| 21:55 | `a3a9328` | `odo_theme` sub-theme, accessible dataset chart; SEO and analytics docs |
| 22:02 | `ff61a90` | Theme live; accessibility and performance fixes (Lighthouse 100 in all four categories); nightly backups with restore test; demo content |
| 22:05 | `aaaeda1` | Backlog of real issues; 12 GitHub issues, 7 closed; project board |
| 22:05 | `7974141` | `composer.lock` hash refreshed (found while installing staging) |
| 22:07 | `e990caf` | README |

## 15 Nov 2026

| Change |
|---|
| Full documentation set: architecture, server setup, content model, configuration management, module and theme guides, operations, security, SEO audit template, changelog, remaining work |
| Google Tag Manager with consent mode (`d30f252`); Search Console verified (`8b63f36`); sitemap base URL fixed, 63 URLs accepted |
| End-to-end analytics check script (`0598546`) |
| Staging environment live at https://staging.observatory.raiuz.site (noindex, no analytics, own database) |
| Update rehearsal on staging (drupal/token 1.17.0 to 1.18.0); first production deploy by the runbook (`ec12bca`, tag `pre-deploy-20261114-2256`); production now installs without dev tools |
