# Analytics measurement plan (GA4 + Looker Studio)

This is a demo property with very little traffic. Figures demonstrate the setup and reporting method, not audience insights.

## Metrics

| # | Metric | Question it answers | GA4 source | Looker Studio chart |
|---|---|---|---|---|
| 1 | Engaged sessions | Are visitors reading, not bouncing? | Built-in `engaged_sessions` | Scorecard + trend line |
| 2 | Dataset page views | Which datasets get used? | `page_view`, filtered on page path `/datasets/` | Table: page title, views |
| 3 | Source data clicks | Do visitors go on to the World Bank source? | Enhanced measurement outbound `click` (link domain `data.worldbank.org`) | Scorecard |
| 4 | Report downloads | Are report PDFs downloaded? | Enhanced measurement `file_download` | Table: file name, count |
| 5 | Site search usage | What do people look for? | `view_search_results`, search parameter `keys` | Table: search term, count |
| 6 | Searches with no result | What content is missing? | Search terms cross-checked with zero-result pages | Table |
| 7 | Returning users | Does the site build a repeat audience? | New vs returning users | Pie chart |
| 8 | Views by topic and region | Which subjects matter most? | Custom dimensions `topic`, `region` from the data layer | Bar charts |

## Configuration

- Enhanced measurement on: page views, scrolls, outbound clicks, site search (query parameter `keys`), file downloads.
- Custom dimensions (event scope): `content_type`, `topic`, `region`, pushed to `dataLayer` on node pages.
- Internal traffic filter for the site owner.

## Privacy

- No personal data is sent; search terms are short keywords.
- Consent mode: analytics storage denied until accepted.
- GA4 does not log or store IP addresses.
- Data retention: 2 months (GA4 minimum).

## Implementation (as built, 15 Nov 2026)

- Google Tag Manager container `GTM-T44MKJ8C`, added by `odo_core_page_attachments()` in
  `web/modules/custom/odo_core/odo_core.module`.
- The ID is read from `$settings['odo_gtm_id']` in production `settings.php` only, so staging
  and local copies send nothing. The value must match `GTM-` or `G-` plus 4 to 12 characters.
- Not added on admin routes.
- Consent mode defaults to denied for analytics and ads storage. `odo_core/js/consent.js` shows
  an "Allow analytics / No thanks" banner and stores the choice in `localStorage` (`odo_consent`).
- Each page pushes `content_type`, `topic` and `region` to `dataLayer` for custom dimensions.
- GA4 itself is configured inside Tag Manager (Google tag with the GA4 Measurement ID,
  trigger Initialization - All Pages).

## Verification (15 Nov 2026)

`scripts/analytics-check.mjs` (headless Chrome on the server) loads the site, clicks
"Allow analytics" and records GA4 requests:

| Step | Result |
|---|---|
| First visit | Consent banner shown |
| Before consent | `page_view` sent as a cookieless ping (`gcs=G100`, storage denied); no `_ga` cookies |
| After consent | `page_view` with `gcs=G101` (analytics granted, ads denied); `_ga` and `_ga_P66SFZQDY6` cookies set |
| Destination | GA4 stream `G-P66SFZQDY6` (Open Data Observatory). Hits also go to `G-0GJBWBCZM7`, a second destination linked to the Google tag; unlink it if not needed. |

Search Console: property verified with the Metatag Verification tag; `sitemap.xml` submitted
and read successfully (63 URLs) after the base URL fix.

Run again: `/opt/node/bin/node scripts/analytics-check.mjs`
