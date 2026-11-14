# Content model

Created by `scripts/content-model.php` (idempotent) and exported to `config/sync`.

## Content types

| Type (machine name) | Purpose | Count | Fields |
|---|---|---|---|
| Report (`report`) | Country comparison publications | 5 | Body, Region, Topic (multiple), Publication year (required), Report PDF |
| Dataset (`dataset`) | One indicator series for one country | 40 | Body, Indicator code (required), Country code ISO3 (required), Latest year, Latest value, Series data (JSON), Source link, Region, Topic |
| News (`article`) | Announcements | 3 | Body, Region, Topic |
| Team Member (`team_member`) | People | 1 | Body, Job title (required), Photo (alt text required) |
| Basic page (`page`) | Static pages such as About | 1 | Body |

Counts as of 15 Nov 2026.

### Why datasets are one node per series

Each dataset has its own URL, title and meta description, so every country-indicator
combination can be found in search and linked from reports. The full series is stored as
JSON in `field_series`; the latest year and value are separate fields so Views can sort and
filter on them.

## Taxonomies

| Vocabulary | Terms |
|---|---|
| Region (`region`) | Middle East & North Africa, Sub-Saharan Africa, Europe & Central Asia, South Asia, East Asia & Pacific, Latin America & Caribbean, North America |
| Topic (`topic`) | Population, Economy, Health, Education, Environment, Energy |

## Data sources

| Indicator code | Name | Topic |
|---|---|---|
| `SP.POP.TOTL` | Population, total | Population |
| `NY.GDP.PCAP.CD` | GDP per capita (current US$) | Economy |
| `SP.DYN.LE00.IN` | Life expectancy at birth | Health |
| `EG.ELC.ACCS.ZS` | Access to electricity (% of population) | Energy |
| `AG.LND.FRST.ZS` | Forest area (% of land area) | Environment |

Countries: Lebanon, Jordan, Egypt (Middle East & North Africa); Nigeria, Kenya (Sub-Saharan
Africa); Germany (Europe & Central Asia); India (South Asia); Brazil (Latin America & Caribbean).

## Listing pages (Views)

| URL | View | Display | Exposed filters |
|---|---|---|---|
| `/reports` | `reports` | Teasers | Region, Topic, Year |
| `/datasets` | `datasets` | Table: title, country, topic, latest year, latest value | Region, Topic, Country code |
| `/news` | `news` | Teasers | Topic |
| `/team` | `team` | Teasers | none |
| `/search?keys=` | `site_search` | Title, type, trimmed body | Keywords (all words, title and body) |

## Roles

| Role | Can |
|---|---|
| Anonymous | View published content and search |
| Content editor | Create, edit and delete own Reports, News and Team Members; edit any; edit (not create or delete) Datasets; manage Region and Topic terms; manage URL aliases; view revisions and site reports |
| Administrator | Everything |

Datasets cannot be created or deleted by editors so that the importer stays the source of truth.
