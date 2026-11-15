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
function odo_listing(string $id, string $label, string $path, string $bundle, array $exposed, array $table_fields = [], string $description = ''): void {
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
  $displays['page_1']['display_options']['display_extenders'] = $description === '' ? [] : [
    'metatag_display_extender' => ['metatags' => ['description' => $description], 'tokenize' => FALSE],
  ];
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
    foreach ($table_fields as $f) {
      [$table, $field, $flabel] = $f;
      $exec->addHandler('default', 'field', $table, $field, ['label' => $flabel] + ($f[3] ?? []));
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
], [], 'Country comparison reports on population, GDP per capita, life expectancy, access to electricity and forest area, built from World Bank Open Data.');

odo_listing('datasets', 'Datasets', 'datasets', 'dataset', [
  ['node__field_region', 'field_region_target_id', 'region', 'Region', $term_filter('region')],
  ['node__field_topic', 'field_topic_target_id', 'topic', 'Topic', $term_filter('topic')],
  ['node__field_country_code', 'field_country_code_value', 'country', 'Country code', ['operator' => '=']],
], [
  ['node__field_country_code', 'field_country_code', 'Country'],
  ['node__field_topic', 'field_topic', 'Topic'],
  ['node__field_latest_year', 'field_latest_year', 'Latest year'],
  ['node__field_latest_value', 'field_latest_value', 'Latest value', ['type' => 'number_decimal', 'settings' => ['thousand_separator' => ',', 'decimal_separator' => '.', 'scale' => 2, 'prefix_suffix' => TRUE]]],
], 'Browse 40 World Bank indicator datasets for eight countries. Filter by region, topic or country and view each series as a chart and table.');

odo_listing('news', 'News', 'news', 'article', [
  ['node__field_topic', 'field_topic_target_id', 'topic', 'Topic', $term_filter('topic')],
], [], 'News and updates from the Open Data Observatory.');

/**
 * Site search: one keyword box over titles and body text of all content.
 *
 * Core's node_search plugin is a deprecated stub on Drupal 11.4 (it moved to
 * the search_node module, which requires core 11.5), so search is built with
 * a Views "combine" filter instead.
 */
function odo_search_view(): void {
  if ($existing = View::load('site_search')) {
    $existing->delete();
  }
  $view = View::load('frontpage')->createDuplicate();
  $view->set('id', 'site_search');
  $view->set('label', 'Search');
  $view->set('description', 'Keyword search across all published content.');
  $displays = $view->get('display');
  unset($displays['feed_1']);
  $displays['default']['display_options']['title'] = 'Search';
  $displays['default']['display_options']['pager'] = ['type' => 'full', 'options' => ['items_per_page' => 20]];
  $displays['default']['display_options']['empty'] = [];
  $displays['default']['display_options']['exposed_form'] = ['type' => 'basic', 'options' => ['submit_button' => 'Search']];
  $displays['page_1']['display_options']['path'] = 'search';
  $displays['page_1']['display_options']['display_extenders'] = [];
  $view->set('display', $displays);

  $exec = $view->getExecutable();
  $exec->setDisplay('default');
  $exec->removeHandler('default', 'filter', 'promote');
  $exec->removeHandler('default', 'sort', 'sticky');
  $exec->displayHandlers->get('default')->setOption('row', ['type' => 'fields']);
  $exec->addHandler('default', 'field', 'node_field_data', 'title', ['label' => '', 'settings' => ['link_to_entity' => TRUE]]);
  $exec->addHandler('default', 'field', 'node_field_data', 'type', ['label' => 'Type']);
  $exec->addHandler('default', 'field', 'node__body', 'body', [
    'label' => '',
    'type' => 'text_summary_or_trimmed',
    'settings' => ['trim_length' => 200],
  ]);
  $exec->addHandler('default', 'filter', 'views', 'combine', [
    'operator' => 'allwords',
    'fields' => ['title' => 'title', 'body' => 'body'],
    'exposed' => TRUE,
    'expose' => [
      'identifier' => 'keys',
      'label' => 'Search',
      'operator_id' => 'keys_op',
      'remember' => FALSE,
      'placeholder' => 'Country, indicator or topic',
    ],
  ]);
  $exec->addHandler('default', 'filter', 'node_field_data', 'type', [
    'value' => ['report' => 'report', 'dataset' => 'dataset', 'article' => 'article', 'team_member' => 'team_member'],
  ]);
  $view->save();
  echo "View ready: /search\n";
}

odo_listing('team', 'Team', 'team', 'team_member', [], [], 'The people who build and run the Open Data Observatory.');

odo_search_view();
