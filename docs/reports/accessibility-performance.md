# Accessibility and performance audit

Date: 14 Nov 2026. Tool: Lighthouse (mobile, default simulated throttling),
run from the server with `scripts/lighthouse.sh`. Raw summaries:
`lighthouse/baseline/summary.txt` and `lighthouse/after/summary.txt`.

Note: audits ran from the same server as the site. Simulated throttling models
a mobile connection, but real-world LCP will be higher for distant visitors.
Field data (Core Web Vitals from real users) needs traffic and Search Console.

## Scores

| Page | Perf | A11y | Best practices | SEO | LCP |
|---|---|---|---|---|---|
| Home (before) | 99 | 96 | 100 | 100 | 1.7 s |
| Home (after) | 100 | 100 | 100 | 100 | 1.2 s |
| Datasets list (before) | 99 | 99 | 100 | 92 | 1.7 s |
| Datasets list (after) | 100 | 100 | 100 | 100 | 1.2 s |
| Dataset page (before) | 99 | 100 | 100 | 100 | 1.7 s |
| Dataset page (after) | 100 | 100 | 100 | 100 | 1.2 s |
| Reports list (before) | 99 | 100 | 100 | 92 | 1.7 s |
| Reports list (after) | 100 | 100 | 100 | 100 | 1.2 s |

The baseline used core's Olivero theme, which already scores well, so gains are small.

## Findings and fixes

| Finding (baseline) | WCAG / area | Fix |
|---|---|---|
| Links in body text relied on colour only (home) | 1.4.1 Use of Color | Underline links in running text (`odo_theme/css/global.css`) |
| Heading levels skipped h1 to h4 (datasets) | 1.3.1 Info and Relationships | Core pager heading set to h2 in `odo_theme_preprocess_pager()` |
| No meta description on listing pages | SEO | Per-view descriptions via Metatag Views |
| HTTP/1.1 only | Performance | HTTP/2 enabled in Nginx |
| Page cache max-age 0 | Performance | Page cache max-age 900 s (cache tags clear it on edits) |

CSS/JS aggregation was already on in the default install, so it is not counted as a change.

## Regression found and fixed during the work

Switching to the sub-theme dropped Best Practices from 100 to 96 on every page:
the theme referenced a `logo.svg` that did not exist (a 404 and an unsized
image). Fixed by turning off the logo image in the branding block; the site
name is shown as text. Re-audited to 100.

## Also done for accessibility (not measured by these audits)

- Dataset charts are drawn from the same data as an accessible table with a
  caption and row and column headers; the SVG has a text summary as its label.
- Visible focus outline on all interactive elements.
- `prefers-reduced-motion` respected.
- Text colours meet WCAG AAA contrast (teal #0b5563 on white is 7.9:1).

## Known, not fixed

- "Reduce unused CSS" and "render-blocking requests" come from Olivero's base
  stylesheets. Fixing them means replacing the base theme, which is out of scope.
- Automated audits catch roughly a third of accessibility issues. A manual
  keyboard and screen reader check is still needed before claiming WCAG AA.
