# SEO site architecture (implemented)

This describes what is configured on https://observatory.raiuz.site, not a proposal.
Configuration lives in `config/sync` and is created by `scripts/seo.php`.

## Sections

| Section | URL | Content | Built with |
|---|---|---|---|
| Reports | `/reports` | Country comparisons per indicator | View `reports`, filters: region, topic, year |
| Datasets | `/datasets` | 40 series (8 countries x 5 indicators) | View `datasets` (table), filters: region, topic, country |
| News | `/news` | Project announcements | View `news`, filter: topic |
| Team | `/team` | Project author | View `team` |
| Search | `/search?keys=` | Keyword search across all content | View `site_search` (combine filter on title and body) |
| About | footer link | Demonstration notice, data licence, source code | Basic page |

## URL patterns (Pathauto)

| Content | Pattern | Example |
|---|---|---|
| Report | `reports/[node:title]` | `/reports/population-total-country-comparison` |
| Dataset | `datasets/[node:field_country_code]/[node:field_indicator_code]` | `/datasets/lbn/sp-pop-totl` |
| News | `news/[node:created:custom:Y]/[node:title]` | `/news/2026/observatory-site-launched` |
| Team member | `team/[node:title]` | `/team/fahed-daibes` |
| Topic term | `topics/[term:name]` | `/topics/energy` |
| Region term | `regions/[term:name]` | `/regions/sub-saharan-africa` |

Dots and underscores in codes are replaced by hyphens rather than removed, so `SP.POP.TOTL` becomes `sp-pop-totl`.
When a title changes, Pathauto keeps the old alias and the Redirect module sends visitors and crawlers to the new URL.

Design choice: dataset URLs use stable codes, not titles, so links survive if the World Bank renames an indicator.

## Meta tags (Metatag)

All content types: `<title>` is `[node:title] | [site:name]`, description is the node summary, plus canonical URL, Open Graph and Twitter card tags.
Front page: a fixed site description.

## Structured data (Schema.org Metatag)

| Page | Type |
|---|---|
| Report | `Report` |
| News | `NewsArticle` |
| Dataset | `WebPage` (Schema.org Metatag has no `Dataset` type; a custom JSON-LD block is a possible improvement) |
| Team member | `Person` |
| Front page | `Organization`, `WebSite` |

## Sitemap and robots

- Simple XML Sitemap at `/sitemap.xml` includes reports (priority 0.8), datasets (0.7), news (0.6), team (0.3) and both taxonomies (0.5).
- `robots.txt` lists the sitemap. The line is added through Composer scaffold `append` (`assets/robots-append.txt`) so it survives core updates.

## Internal linking

- Each report table links to every dataset it summarises.
- Dataset pages link to their topic and region terms and to the World Bank source.
- Main menu: Reports, Datasets, News, Team. Footer: About.
