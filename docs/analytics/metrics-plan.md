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
