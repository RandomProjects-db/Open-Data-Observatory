<?php

/**
 * @file
 * Creates the Reports, Datasets and News listing pages.
 *
 * Each view is cloned from core's "frontpage" view (so all required display
 * defaults exist) and then adjusted through the ViewExecutable handler API,
 * which fills in handler defaults correctly. Idempotent: existing views are
 * deleted and rebuilt.
 *
 * Run: vendor/bin/drush php:script scripts/views.php
 */

use Drupal\views\Entity\View;

/**
 * Builds a listing view for one content type.
 */
function odo_listing(string $id, string $label, string $path, string $bundle, array $exposed, array $table_fields = []): void {
  if ($existing = View::load($id)) {
    $existing->delete();
  }

  $view = View::load('frontpage')->createDuplicate();
  $view->set('id', $id);
  $view->set('label', $label);
  $view->set('description', "Listing of $label with filters.");

  $displays = $view->get('display');
  unset($displays['feed_1']);
  $displays['default']['display_options']['title'] = $label;
  $displays['default']['display_options']['pager'] = ['type' => 'full', 'options' => ['items_per_page' => 20]];
  // Clone carries the frontpage's empty-text and feed links; drop them.
  $displays['default']['display_options']['empty'] = [];
  $displays['page_1']['display_options']['path'] = $path;
  $displays['page_1']['display_options']['display_extenders'] = [];
  unset($displays['page_1']['display_options']['display_description']);
  $displays['page_1']['display_options']['menu'] = [
    'type' => 'normal',
    'title' => $label,
    'menu_name' => 'main',
    'weight' => count(\Drupal::entityTypeManager()->getStorage('view')->loadMultiple()),
  ];
  $view->set('display', $displays);

  $exec = $view->getExecutable();
  $exec->setDisplay('default');
  $exec->removeHandler('default', 'filter', 'promote');
  $exec->removeHandler('default', 'sort', 'sticky');
  $exec->addHandler('default', 'filter', 'node_field_data', 'type', [
    'value' => [$bundle => $bundle],
  ]);

  foreach ($exposed as $filter) {
    [$table, $field, $identifier, $flabel, $extra] = $filter + [4 => []];
    $exec->addHandler('default', 'filter', $table, $field, $extra + [
      'exposed' => TRUE,
      'expose' => [
        'identifier' => $identifier,
        'label' => $flabel,
        'operator_id' => $identifier . '_op',
        'remember' => FALSE,
      ],
    ]);
  }

  if ($table_fields) {
    $display_options = &$exec->displayHandlers->get('default')->options;
    $display_options['style'] = ['type' => 'table', 'options' => ['sticky' => TRUE]];
    $display_options['row'] = ['type' => 'fields'];
    $display_options['defaults']['style'] = FALSE;
    $display_options['defaults']['row'] = FALSE;
    $exec->displayHandlers->get('default')->setOption('style', $display_options['style']);
    $exec->displayHandlers->get('default')->setOption('row', $display_options['row']);
    $exec->addHandler('default', 'field', 'node_field_data', 'title', [
      'label' => 'Title',
      'settings' => ['link_to_entity' => TRUE],
    ]);
    foreach ($table_fields as [$table, $field, $flabel]) {
      $exec->addHandler('default', 'field', $table, $field, ['label' => $flabel]);
    }
  }

  $view->save();
  echo "View ready: /$path\n";
}

$term_filter = fn (string $vid): array => [
  'type' => 'select',
  'vid' => $vid,
  'hierarchy' => FALSE,
  'limit' => TRUE,
];

odo_listing('reports', 'Reports', 'reports', 'report', [
  ['node__field_region', 'field_region_target_id', 'region', 'Region', $term_filter('region')],
  ['node__field_topic', 'field_topic_target_id', 'topic', 'Topic', $term_filter('topic')],
  ['node__field_year', 'field_year_value', 'year', 'Year'],
]);

odo_listing('datasets', 'Datasets', 'datasets', 'dataset', [
  ['node__field_region', 'field_region_target_id', 'region', 'Region', $term_filter('region')],
  ['node__field_topic', 'field_topic_target_id', 'topic', 'Topic', $term_filter('topic')],
  ['node__field_country_code', 'field_country_code_value', 'country', 'Country code', ['operator' => '=']],
], [
  ['node__field_country_code', 'field_country_code', 'Country'],
  ['node__field_topic', 'field_topic', 'Topic'],
  ['node__field_latest_year', 'field_latest_year', 'Latest year'],
  ['node__field_latest_value', 'field_latest_value', 'Latest value'],
]);

odo_listing('news', 'News', 'news', 'article', [
  ['node__field_topic', 'field_topic_target_id', 'topic', 'Topic', $term_filter('topic')],
]);
