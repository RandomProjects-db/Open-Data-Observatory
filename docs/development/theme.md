# odo_theme

Sub-theme of core's Olivero in `web/themes/custom/odo_theme`.

## Why a sub-theme of Olivero

Olivero is core's accessible, maintained front-end theme. A sub-theme inherits its markup,
templates and updates, and only overrides what this site needs. The baseline audit
already scored 96 to 100 for accessibility, so the work was targeted fixes, not a rebuild.

## Files

| File | Role |
|---|---|
| `odo_theme.info.yml` | Base theme, library, regions (copied from Olivero: sub-themes do not inherit regions) |
| `odo_theme.libraries.yml` | `global` (site CSS) and `dataset-chart` (chart CSS and JS) |
| `odo_theme.theme` | Preprocess hooks |
| `css/global.css` | Brand colour, focus outlines, tables, filters, link underlines, reduced motion |
| `css/dataset-chart.css` | Chart styles |
| `js/dataset-chart.js` | SVG line chart (no external library) |
| `templates/node--dataset--full.html.twig` | Dataset page layout with chart and data table |

## Preprocess hooks

- `odo_theme_preprocess_node()`: on full Dataset pages, decodes `field_series` JSON once and
  passes `series` (array) and `series_json` (validated string) to the template. Invalid JSON
  gives an empty series and no chart.
- `odo_theme_preprocess_pager()`: sets `pagination_heading_level` to `h2`. Core's default
  `h4` directly after the page `h1` skipped levels (Lighthouse "heading-order", WCAG 1.3.1).

## Dataset chart

- The data table is the source of truth: `<caption>`, `<th scope="col">` and
  `<th scope="row">`, inside a `<details>` element so it does not dominate the page.
- `js/dataset-chart.js` draws the SVG from the same JSON as a progressive enhancement.
  Without JavaScript, the table is still there.
- The SVG has `role="img"` and an `aria-label` summary, on the Lebanon population page: "Line chart from 1990
  to 2025. Value went from 3.6M to 5.8M. Full figures are in the data table below."
- Uses `Drupal.behaviors` and `once()`, so it works with BigPipe and AJAX.
- Numbers use `Intl.NumberFormat` compact notation (for example 5.4M).

## Accessibility choices

| Choice | Reason |
|---|---|
| Brand teal `#0b5563`, text greys `#1b1f23` and `#4a5560` | Contrast 7.4:1 or higher on white (WCAG AAA for text) |
| 3 px `:focus-visible` outline | Keyboard users see where they are (WCAG 2.4.7) |
| Underlined links in running text | Not colour alone (WCAG 1.4.1) |
| `prefers-reduced-motion` | Disables animation and smooth scrolling (WCAG 2.3.3) |
| Logo image off; site name as text | The sub-theme had no logo file (404, unsized image) |

## Changing styles

1. Edit files in `css/` or `js/`.
2. `drush cache:rebuild` (CSS/JS aggregation is on in production).
3. Run `scripts/smoke-test.sh` and `scripts/lighthouse.sh <label>`; compare with
   `docs/reports/lighthouse/after/summary.txt`.
