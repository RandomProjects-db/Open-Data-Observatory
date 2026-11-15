# Editor Guide

For content editors of the Open Data Observatory. No technical knowledge needed.

## 1. Getting started

Log in at https://observatory.raiuz.site/user/login. You have the **Content editor** role. You can create and edit content and assign taxonomy terms. You cannot change site settings.

[Screenshot: login page]

[Screenshot: admin toolbar with the Content link highlighted]

## 2. Content types

### Report
Fields: **Title**, **Body**, **Region**, **Topic**, **Publication year** (required), **Report PDF**.

### Dataset
Datasets are normally created and updated by the importer. Check them, but do not edit imported values by hand, because the next import overwrites them. Fields: Indicator code, Country code (ISO3), Latest year, Latest value, Series data (JSON), Source link, Region, Topic. If a value looks wrong, report it through a GitHub issue.

### News
Fields: **Title**, **Body**, **Region**, **Topic**.

### Team Member
Fields: **Title** (the person's name), **Job title**, **Photo** (alt text is required), **Body** (short biography).

## 3. Taxonomies

**Region** and **Topic** are lists of terms. Choose from the existing terms. If none fits, ask the web team before adding a new term, so the lists stay consistent.

[Screenshot: Region and Topic fields on a content form]

## 4. Create content

1. Go to **Content > Add content** and choose the type.
2. Fill in all fields. Required fields are marked with a red asterisk.
3. Add the summary (see section 7).
4. Leave **Published** unchecked if you are not ready.
5. Click **Save**.

[Screenshot: Add content page]

[Screenshot: Report form with required fields marked]

## 5. Edit content

1. Go to **Content**, find the item (filter by type or search by title), and click **Edit**.
2. Make changes and click **Save**.

[Screenshot: Content list with filters]

## 6. Publish and unpublish

On the edit form, check **Published** to make the item public, or uncheck it to hide it. Open the live page afterwards and check it looks right.

[Screenshot: Published checkbox on the edit form]

## 7. Writing for accessibility and search

### Alt text for images
Alt text describes an image for people who cannot see it. It is required for Team Member photos.
- Describe what matters in the image, in one short sentence. Example: "Maria Lopez, senior data analyst, smiling at her desk".
- Do not start with "image of" or "photo of".
- For charts, state the main finding, and give the full data in the text of the page.

[Screenshot: Photo field with alt text filled in]

### Link text
Link text must make sense on its own.
- Good: "Read the 2024 Health Report".
- Avoid: "click here", "read more", or a bare web address.
- If a link opens a PDF, say so: "Annual report (PDF)".

### Headings
- The page title is Heading 1. Do not use Heading 1 in the body.
- Start body sections with Heading 2, then Heading 3 for sub-sections. Do not skip levels.
- Use the heading styles in the editor, not bold text, to make something look like a heading.

[Screenshot: editor toolbar with the heading dropdown open]

### Summary for SEO
Write a short summary (about 150 characters) that explains the page in plain words. Search engines show it under the page title.
- Put the main topic first.
- Make each summary unique.
- Do not copy the first sentence of the body without checking it reads well alone.

[Screenshot: Summary or meta description field]

## 8. Getting help

Open a GitHub issue using the matching template, or contact the web team. See the triage policy for response times.
