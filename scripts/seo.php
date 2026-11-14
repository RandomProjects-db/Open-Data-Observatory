<?php

/**
 * @file
 * SEO, search and editor-permission setup. Idempotent.
 *
 * Run: vendor/bin/drush php:script scripts/seo.php
 */

use Drupal\metatag\Entity\MetatagDefaults;
use Drupal\pathauto\Entity\PathautoPattern;
use Drupal\user\Entity\Role;

// --- Pathauto: readable, stable URLs. ------------------------------------
// Replace punctuation such as the dots in "SP.POP.TOTL" with the separator
// instead of deleting it, so codes stay readable (sp-pop-totl).
\Drupal::configFactory()->getEditable('pathauto.settings')
  ->set('punctuation.period', 1)
  ->set('punctuation.underscore', 1)
  ->set('update_action', 2)
  ->save();

$patterns = [
  ['report', 'node', 'report', 'reports/[node:title]'],
  ['dataset', 'node', 'dataset', 'datasets/[node:field_country_code]/[node:field_indicator_code]'],
  ['news', 'node', 'article', 'news/[node:created:custom:Y]/[node:title]'],
  ['team_member', 'node', 'team_member', 'team/[node:title]'],
  ['topic', 'taxonomy_term', 'topic', 'topics/[term:name]'],
  ['region', 'taxonomy_term', 'region', 'regions/[term:name]'],
];
foreach ($patterns as $weight => [$id, $entity_type, $bundle, $pattern]) {
  if ($existing = PathautoPattern::load($id)) {
    $existing->delete();
  }
  $p = PathautoPattern::create([
    'id' => $id,
    'label' => ucfirst(str_replace('_', ' ', $bundle)),
    'type' => "canonical_entities:$entity_type",
    'pattern' => $pattern,
    'weight' => $weight,
  ]);
  $p->addSelectionCondition([
    'id' => "entity_bundle:$entity_type",
    'bundles' => [$bundle => $bundle],
    'negate' => FALSE,
    'context_mapping' => [$entity_type => $entity_type],
  ]);
  $p->save();
}
echo "Pathauto patterns: " . count($patterns) . "\n";

// --- Metatag: per-type titles, descriptions, social and Schema.org. ------
$common = [
  'title' => '[node:title] | [site:name]',
  'description' => '[node:summary]',
  'canonical_url' => '[node:url]',
  'og_title' => '[node:title]',
  'og_description' => '[node:summary]',
  'og_url' => '[node:url]',
  'og_site_name' => '[site:name]',
  'twitter_cards_type' => 'summary',
  'twitter_cards_title' => '[node:title]',
];
$article_schema = fn (string $type): array => [
  'schema_article_type' => $type,
  'schema_article_headline' => '[node:title]',
  'schema_article_description' => '[node:summary]',
  'schema_article_date_published' => '[node:created:html_datetime]',
  'schema_article_date_modified' => '[node:changed:html_datetime]',
  'schema_article_publisher' => serialize(['@type' => 'Organization', 'name' => '[site:name]', 'url' => '[site:url]']),
];
$defaults = [
  'node__report' => ['Content: Report', $common + ['og_type' => 'article'] + $article_schema('Report')],
  'node__article' => ['Content: News', $common + ['og_type' => 'article'] + $article_schema('NewsArticle')],
  'node__dataset' => ['Content: Dataset', $common + [
    'og_type' => 'website',
    // Schema.org Metatag has no Dataset type; describe the page as a WebPage.
    'schema_web_page_type' => 'WebPage',
    'schema_web_page_name' => '[node:title]',
    'schema_web_page_description' => '[node:summary]',
  ]],
  'node__team_member' => ['Content: Team Member', $common + [
    'og_type' => 'profile',
    'schema_person_type' => 'Person',
    'schema_person_name' => '[node:title]',
  ]],
];
foreach ($defaults as $id => [$label, $tags]) {
  $entity = MetatagDefaults::load($id) ?? MetatagDefaults::create(['id' => $id, 'label' => $label]);
  $entity->set('tags', $tags)->save();
}
// Site-wide Organization and WebSite markup on the front page.
$front = MetatagDefaults::load('front');
if ($front) {
  $front->set('tags', array_merge($front->get('tags') ?? [], [
    'description' => 'Open Data Observatory publishes reports and World Bank indicator datasets on population, economy, health, energy and environment.',
    'schema_organization_type' => 'Organization',
    'schema_organization_name' => '[site:name]',
    'schema_organization_url' => '[site:url]',
    'schema_web_site_type' => 'WebSite',
    'schema_web_site_name' => '[site:name]',
    'schema_web_site_url' => '[site:url]',
  ]))->save();
}
echo "Metatag defaults: " . count($defaults) . " content types + front page\n";

// --- XML sitemap: include all public content types and taxonomies. -------
$sitemap = \Drupal::service('simple_sitemap.entity_manager');
foreach (['report' => 0.8, 'dataset' => 0.7, 'article' => 0.6, 'team_member' => 0.3] as $bundle => $priority) {
  $sitemap->setBundleSettings('node', $bundle, ['index' => TRUE, 'priority' => $priority, 'changefreq' => 'weekly']);
}
foreach (['region', 'topic'] as $vid) {
  $sitemap->setBundleSettings('taxonomy_term', $vid, ['index' => TRUE, 'priority' => 0.5, 'changefreq' => 'monthly']);
}
echo "Sitemap bundles configured\n";

// --- Permissions. --------------------------------------------------------
// Core Search is not used (see scripts/views.php: site_search view).
$editor = Role::load('content_editor');
foreach (['report', 'article', 'team_member'] as $type) {
  foreach (['create', 'edit own', 'edit any', 'delete own'] as $op) {
    $editor->grantPermission("$op $type content");
  }
}
// Datasets come from the importer: editors may correct them but not create
// or delete them, so the importer stays the source of truth.
$editor->grantPermission('edit any dataset content');
foreach (['create terms in topic', 'edit terms in topic', 'create terms in region', 'edit terms in region', 'create url aliases', 'access site reports'] as $perm) {
  $editor->grantPermission($perm);
}
$editor->save();
echo "Permissions updated (content_editor)\n";
