<?php

/**
 * @file
 * Creates honest demo content: data-driven reports, project news, an About
 * page and the project author. Idempotent (matches on title).
 *
 * Run after the World Bank import:
 *   vendor/bin/drush php:script scripts/content.php
 */

use Drupal\Component\Utility\Html;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\Entity\Node;
use Drupal\odo_core\WorldBankImporter;

$storage = \Drupal::entityTypeManager()->getStorage('node');
$terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

/**
 * Creates or updates a node matched by type and title.
 */
function odo_upsert(string $type, string $title, array $values): Node {
  $storage = \Drupal::entityTypeManager()->getStorage('node');
  $found = $storage->loadByProperties(['type' => $type, 'title' => $title]);
  $node = $found ? reset($found) : Node::create(['type' => $type, 'title' => $title, 'uid' => 1]);
  foreach ($values as $field => $value) {
    $node->set($field, $value);
  }
  $node->setPublished()->save();
  return $node;
}

/**
 * Returns a term ID by vocabulary and name.
 */
function odo_tid(string $vid, string $name): ?int {
  $found = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => $vid, 'name' => $name]);
  return $found ? (int) reset($found)->id() : NULL;
}

// --- Reports: one per indicator, written from the imported data. ---------
$report_count = 0;
foreach (WorldBankImporter::INDICATORS as $code => $topic) {
  $rows = [];
  $indicator_name = '';
  $max_year = 0;
  foreach ($storage->loadByProperties(['type' => 'dataset', 'field_indicator_code' => $code]) as $dataset) {
    [$name, $country] = array_map('trim', explode(':', $dataset->label(), 2)) + [1 => ''];
    $indicator_name = $name;
    $year = (int) $dataset->get('field_latest_year')->value;
    $max_year = max($max_year, $year);
    $rows[] = [$country, $year, (float) $dataset->get('field_latest_value')->value, $dataset->toUrl()->toString()];
  }
  if (count($rows) < 2) {
    continue;
  }
  usort($rows, fn ($a, $b) => $b[2] <=> $a[2]);
  $high = $rows[0];
  $low = end($rows);
  $fmt = fn (float $v): string => $v >= 1000 ? number_format($v) : number_format($v, 1);

  $table = '';
  foreach ($rows as [$country, $year, $value, $url]) {
    $table .= sprintf('<tr><th scope="row"><a href="%s">%s</a></th><td>%d</td><td>%s</td></tr>', $url, Html::escape($country), $year, $fmt($value));
  }
  $summary = sprintf(
    '%s across %d countries: %s has the highest latest value (%s) and %s the lowest (%s).',
    $indicator_name, count($rows), $high[0], $fmt($high[2]), $low[0], $fmt($low[2]),
  );
  $body = sprintf(
    '<p>%s</p><p>Figures are the most recent year available for each country in World Bank Open Data, so years can differ between countries.</p>'
    . '<table><caption>%s, latest available year</caption><thead><tr><th scope="col">Country</th><th scope="col">Year</th><th scope="col">Value</th></tr></thead><tbody>%s</tbody></table>'
    . '<p>Source: World Bank Open Data, licensed CC BY 4.0. Imported automatically by this site.</p>',
    Html::escape($summary), Html::escape($indicator_name), $table,
  );

  odo_upsert('report', "$indicator_name: country comparison", [
    'body' => ['value' => $body, 'summary' => $summary, 'format' => 'full_html'],
    'field_year' => $max_year ?: (int) date('Y'),
    'field_topic' => odo_tid('topic', $topic),
    'promote' => 1,
  ]);
  $report_count++;
}
echo "Reports: $report_count\n";

// --- News: real events from building this project. -----------------------
$news = [
  ['Observatory site launched', 'The Open Data Observatory is live on Drupal 11, with reports, datasets, news and site search. It is a demonstration project built to show content management, data publishing and web operations practice.'],
  ['World Bank indicators imported', sprintf('The site now holds %d datasets: five World Bank indicators for eight countries. A custom Drupal module imports them and retries when the World Bank API is slow, so updates can be re-run safely.', count($storage->loadByProperties(['type' => 'dataset'])))],
  ['Accessible charts on dataset pages', 'Each dataset page now shows a line chart of its yearly values. The same figures are published in a data table, so the information is available to screen reader and keyboard users.'],
];
foreach ($news as [$title, $text]) {
  odo_upsert('article', $title, [
    'body' => ['value' => "<p>$text</p>", 'summary' => $text, 'format' => 'basic_html'],
    'promote' => 1,
  ]);
}
echo "News: " . count($news) . "\n";

// --- Team: the project author only (no invented people). -----------------
odo_upsert('team_member', 'Fahed Daibes', [
  'field_job_title' => 'Project author: web development and operations',
  'body' => ['value' => '<p>Built and operates this site: Drupal configuration, the custom PHP importer, theme, SEO, analytics and deployment.</p>', 'format' => 'basic_html'],
]);

// --- About page. ---------------------------------------------------------
$about = odo_upsert('page', 'About this site', [
  'body' => [
    'value' => '<p>The Open Data Observatory is a <strong>demonstration project</strong>, not a real organization. It was built by Fahed Daibes to show how a research and data publication website can be built and run on Drupal.</p>'
    . '<h2>Data</h2><p>Datasets come from <a href="https://data.worldbank.org/">World Bank Open Data</a> under the Creative Commons Attribution 4.0 licence. Reports on this site are generated from those figures.</p>'
    . '<h2>Source code</h2><p>The code and configuration are public on <a href="https://github.com/RandomProjects-db/Open-Data-Observatory">GitHub</a>.</p>',
    'format' => 'full_html',
  ],
]);
echo "About page: /node/" . $about->id() . "\n";

// --- Menus. --------------------------------------------------------------
$links = [
  ['main', 'Search', 'internal:/search', 50],
  ['footer', 'About this site', 'entity:node/' . $about->id(), 0],
];
foreach ($links as [$menu, $title, $uri, $weight]) {
  $existing = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['menu_name' => $menu, 'title' => $title]);
  if (!$existing) {
    MenuLinkContent::create(['menu_name' => $menu, 'title' => $title, 'link' => ['uri' => $uri], 'weight' => $weight])->save();
  }
}
echo "Menu links ready\n";
