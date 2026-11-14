<?php

/**
 * @file
 * Creates the Open Data Observatory content model.
 *
 * Idempotent: safe to run more than once. Run with:
 *   vendor/bin/drush php:script scripts/content-model.php
 * then export with `drush cex` so the model lives in config/sync.
 */

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Creates a vocabulary and its terms if missing.
 */
function odo_vocab(string $vid, string $label, array $terms): void {
  if (!Vocabulary::load($vid)) {
    Vocabulary::create(['vid' => $vid, 'name' => $label])->save();
  }
  $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  foreach ($terms as $name) {
    if (!$storage->loadByProperties(['vid' => $vid, 'name' => $name])) {
      Term::create(['vid' => $vid, 'name' => $name])->save();
    }
  }
}

/**
 * Creates a content type if missing.
 */
function odo_type(string $type, string $label, string $description): void {
  if (!NodeType::load($type)) {
    NodeType::create([
      'type' => $type,
      'name' => $label,
      'description' => $description,
    ])->save();
  }
  else {
    // Relabel existing types (e.g. core "Article" becomes "News").
    $node_type = NodeType::load($type);
    $node_type->set('name', $label)->set('description', $description)->save();
  }
}

/**
 * Creates field storage (shared) and attaches the field to a bundle.
 */
function odo_field(string $bundle, string $name, string $type, string $label, array $storage_settings = [], array $field_settings = [], int $cardinality = 1, bool $required = FALSE): void {
  if (!FieldStorageConfig::loadByName('node', $name)) {
    FieldStorageConfig::create([
      'field_name' => $name,
      'entity_type' => 'node',
      'type' => $type,
      'settings' => $storage_settings,
      'cardinality' => $cardinality,
    ])->save();
  }
  if (!FieldConfig::loadByName('node', $bundle, $name)) {
    FieldConfig::create([
      'field_name' => $name,
      'entity_type' => 'node',
      'bundle' => $bundle,
      'label' => $label,
      'settings' => $field_settings,
      'required' => $required,
    ])->save();
  }
  // Show the field on the edit form and the full view.
  $repo = \Drupal::service('entity_display.repository');
  $repo->getFormDisplay('node', $bundle)->setComponent($name)->save();
  $repo->getViewDisplay('node', $bundle)->setComponent($name, ['label' => 'inline'])->save();
}

/**
 * Settings for a taxonomy term reference field.
 */
function odo_term_ref(string $vid): array {
  return [
    'handler' => 'default:taxonomy_term',
    'handler_settings' => ['target_bundles' => [$vid => $vid]],
  ];
}

// Taxonomies.
odo_vocab('region', 'Region', [
  'Middle East & North Africa', 'Sub-Saharan Africa', 'Europe & Central Asia',
  'South Asia', 'East Asia & Pacific', 'Latin America & Caribbean', 'North America',
]);
odo_vocab('topic', 'Topic', [
  'Population', 'Economy', 'Health', 'Education', 'Environment', 'Energy',
]);

// Content types.
odo_type('report', 'Report', 'Research reports and analysis publications.');
odo_type('dataset', 'Dataset', 'Indicator time series imported from open data sources.');
odo_type('article', 'News', 'News and announcements.');
odo_type('team_member', 'Team Member', 'Staff and contributors shown on the team page.');
odo_type('page', 'Basic page', 'Static pages such as About.');

// Body field: core's node_add_body_field() helper fails on Drupal 11.4,
// so attach the existing "body" storage directly.
foreach (['report', 'dataset', 'article', 'team_member', 'page'] as $bundle) {
  odo_field($bundle, 'body', 'text_with_summary', 'Body', [], ['display_summary' => TRUE]);
}

// Shared classification fields.
foreach (['report', 'dataset', 'article'] as $bundle) {
  odo_field($bundle, 'field_region', 'entity_reference', 'Region', ['target_type' => 'taxonomy_term'], odo_term_ref('region'));
  odo_field($bundle, 'field_topic', 'entity_reference', 'Topic', ['target_type' => 'taxonomy_term'], odo_term_ref('topic'), -1);
}

// Report.
odo_field('report', 'field_year', 'integer', 'Publication year', [], ['min' => 1990, 'max' => 2100], 1, TRUE);
odo_field('report', 'field_report_file', 'file', 'Report PDF', [], ['file_extensions' => 'pdf', 'file_directory' => 'reports']);

// Dataset: one node per indicator per country.
odo_field('dataset', 'field_indicator_code', 'string', 'Indicator code', [], [], 1, TRUE);
odo_field('dataset', 'field_country_code', 'string', 'Country code (ISO3)', ['max_length' => 3], [], 1, TRUE);
odo_field('dataset', 'field_latest_year', 'integer', 'Latest year');
odo_field('dataset', 'field_latest_value', 'decimal', 'Latest value', ['precision' => 20, 'scale' => 4]);
odo_field('dataset', 'field_series', 'string_long', 'Series data (JSON)');
odo_field('dataset', 'field_source_url', 'link', 'Source', [], ['link_type' => 16, 'title' => 0]);

// Team member.
odo_field('team_member', 'field_job_title', 'string', 'Job title', [], [], 1, TRUE);
odo_field('team_member', 'field_photo', 'image', 'Photo', [], ['alt_field_required' => TRUE, 'file_directory' => 'team']);

echo "Content model ready.\n";
