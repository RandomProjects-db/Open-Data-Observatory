# Backlog

Real issues from building and running the site, triaged with
`docs/triage-policy.md`. Each level-3 heading becomes a GitHub issue; items
with a **Fixed in** line are closed with a link to the commit.

### Site returns 500 after deploying module files
- **Reported by:** Development (smoke test)
- **Type:** bug
- **Priority:** P1
- **Fixed in:** permissions fix, smoke test added in 1445f98

Every page returned HTTP 500 after module files were synced with rsync. Files arrived with group `ubuntu` and mode 640, so PHP-FPM (`www-data`) could not read `odo_core.info.yml`. Fix: `chgrp -R www-data`, setgid on directories so new files inherit the group, and `scripts/smoke-test.sh` after every deploy.

### World Bank API timeouts fail most of an import
- **Reported by:** Data Science
- **Type:** bug
- **Priority:** P2
- **Fixed in:** d93b03b

The API intermittently accepts connections and sends no data. One import failed 13 of 40 series. Fix: short timeouts and up to 3 attempts with backoff; failures are logged and skipped, and re-runs are idempotent. Re-run imported all 40.

### Site search returns 403 on Drupal 11.4
- **Reported by:** External Affairs
- **Type:** bug
- **Priority:** P2
- **Fixed in:** 7cec251

Core's `node_search` plugin is a deprecated stub on 11.4 that always denies access; its replacement module requires core 11.5. Fix: site search built with a Views combine filter at `/search`.

### Best Practices score dropped to 96 after theme switch
- **Reported by:** Development (Lighthouse)
- **Type:** bug
- **Priority:** P3
- **Fixed in:** ff61a90

The sub-theme referenced a missing `logo.svg` (404 and an unsized image). Fix: logo image turned off in the branding block; site name shown as text.

### Accessibility: links rely on colour; heading levels skip
- **Reported by:** Analysis
- **Type:** accessibility
- **Priority:** P2
- **Fixed in:** ff61a90

Lighthouse baseline flagged WCAG 1.4.1 (links distinguished only by colour) on the home page and 1.3.1 (h1 followed by h4) on /datasets. Fix: underline links in running text; pager heading set to h2.

### Listing pages have no meta description
- **Reported by:** External Affairs
- **Type:** seo
- **Priority:** P3
- **Fixed in:** ff61a90

/reports and /datasets scored SEO 92. Fix: per-view descriptions with Metatag Views. All audited pages now score 100.

### Add a chart to Dataset pages
- **Reported by:** Data Science
- **Type:** feature
- **Priority:** P3
- **Fixed in:** a3a9328

Show each series as a chart, with the same figures in an accessible table.

### Add CSV download for each Dataset
- **Reported by:** Data Science
- **Type:** feature
- **Priority:** P3

Researchers want the series as CSV. Acceptance criteria: a "Download CSV" link on each dataset page; columns year, value; file named with country and indicator codes; tracked as a `file_download` event in GA4.

### Use Dataset structured data on dataset pages
- **Reported by:** External Affairs
- **Type:** seo
- **Priority:** P4

Schema.org Metatag has no Dataset type, so dataset pages use WebPage. Acceptance criteria: JSON-LD `Dataset` with name, description, license (CC BY 4.0), creator (World Bank), temporal coverage; passes Google's Rich Results Test.

### Tag News items with Topic and Region
- **Reported by:** Analysis
- **Type:** content
- **Priority:** P4

News items have no Topic or Region, so they do not appear in filtered views. Acceptance criteria: every News item has at least one Topic; editor guide updated.

### CI pipeline does not run
- **Reported by:** Development
- **Type:** bug
- **Priority:** P2

GitHub Actions jobs are not started because the account is locked for a billing issue. The same checks (PHPCS, PHPUnit) are run on the server before each commit. Acceptance criteria: billing resolved; CI green on main.

### Add a staging environment
- **Reported by:** Development
- **Type:** feature
- **Priority:** P2

Changes are currently tested on production. Acceptance criteria: staging site at staging.observatory.raiuz.site with its own database; deploy runbook followed staging first.
