# Backlog

Each issue is a level-3 heading, followed by metadata and a description, so it can be turned into `gh issue create` commands.

### Dataset page shows stale "Latest year" after import
- **Requesting team:** Data Science
- **Type:** bug
- **Priority:** P2

Dataset nodes keep the old Latest year and Latest value after `drush odo:import-worldbank` runs.
Acceptance criteria: after an import, changed values appear on the dataset page; unchanged datasets are not duplicated; a unit test covers the update case.

### Report PDF link returns 404 for some reports
- **Requesting team:** External Affairs
- **Type:** bug
- **Priority:** P1

Some Report pages link to a PDF that returns a 404 error.
Acceptance criteria: the cause is identified and documented in the issue; all affected reports download their PDF; a check for missing files is added to the post-deploy list.

### Add region and topic filters to the Reports listing
- **Requesting team:** Analysis
- **Type:** feature
- **Priority:** P3

Visitors need to narrow /reports by Region, Topic and Publication year.
Acceptance criteria: filters work alone and together; results can be reset; filter state is kept in the URL.

### Add a chart for Series data on Dataset pages
- **Requesting team:** Data Science
- **Type:** feature
- **Priority:** P3

Show the Series data JSON as a line chart above the data table.
Acceptance criteria: chart renders for all imported datasets; a text table with the same data is present; works without errors on mobile.

### Add CSV download for each Dataset
- **Requesting team:** Analysis
- **Type:** feature
- **Priority:** P3

Visitors should be able to download the series of a dataset as CSV.
Acceptance criteria: a download link on each Dataset page; file includes country code, indicator code, year and value; source link is stated in the page.

### Add a Team page listing Team Members
- **Requesting team:** External Affairs
- **Type:** feature
- **Priority:** P4

Create a /team page listing Team Member content with photo, name and job title.
Acceptance criteria: unpublished members are not shown; every photo has alt text; layout works on mobile.

### Add meta descriptions and Open Graph tags to all content types
- **Requesting team:** External Affairs
- **Type:** seo
- **Priority:** P3

Pages need a unique meta description and social sharing tags.
Acceptance criteria: each content type has a summary-based description; Open Graph title, description and image are present; a sample of pages is checked with a validator [TBD].

### Generate an XML sitemap and robots.txt review
- **Requesting team:** Development
- **Type:** seo
- **Priority:** P3

Search engines need an up-to-date sitemap for reports, datasets and news.
Acceptance criteria: sitemap is available at /sitemap.xml; it lists published content only; it is referenced in robots.txt; staging is excluded from indexing.

### Fix keyboard focus and contrast on the main navigation
- **Requesting team:** Development
- **Type:** accessibility
- **Priority:** P2

The main menu and search field need a visible focus style and enough colour contrast.
Acceptance criteria: all menu items can be reached and used with the keyboard; focus is always visible; text contrast meets WCAG 2.2 AA when checked with a tool [TBD].

### Review and tag existing News and Report content with Region and Topic
- **Requesting team:** Analysis
- **Type:** content
- **Priority:** P4

Older items are missing Region or Topic terms, so they do not appear in filtered lists.
Acceptance criteria: a list of untagged items is produced; every Report and News item has at least one Region and one Topic; changes are made by editors, not in the database.
