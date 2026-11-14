<?php

/**
 * @file
 * Enables the ODO theme, places site blocks and sets performance options.
 *
 * Run: vendor/bin/drush php:script scripts/theme.php
 */

use Drupal\block\Entity\Block;

// Theme. Installing a theme copies the default theme's blocks into matching
// regions, so the Olivero layout (branding, menus, messages) carries over.
\Drupal::service('theme_installer')->install(['odo_theme']);
\Drupal::configFactory()->getEditable('system.theme')->set('default', 'odo_theme')->save();
echo "Default theme: odo_theme\n";

// Latest datasets block (from odo_core) on the front page only.
if (!Block::load('odo_theme_latest_datasets')) {
  Block::create([
    'id' => 'odo_theme_latest_datasets',
    'theme' => 'odo_theme',
    'region' => 'content_below',
    'plugin' => 'odo_latest_datasets',
    'weight' => 0,
    'settings' => ['label' => 'Latest datasets', 'label_display' => 'visible'],
    'visibility' => [
      'request_path' => [
        'id' => 'request_path',
        'pages' => '<front>',
        'negate' => FALSE,
      ],
    ],
  ])->save();
}
echo "Block placed: Latest datasets (front page)\n";

// Front page title instead of core's "Welcome!" placeholder.
\Drupal::configFactory()->getEditable('views.view.frontpage')
  ->set('display.default.display_options.title', 'Open data on development, energy and environment')
  ->save();

// Performance: aggregate CSS/JS and let the page cache serve anonymous
// visitors for 15 minutes (cache tags still clear it on content changes).
\Drupal::configFactory()->getEditable('system.performance')
  ->set('css.preprocess', TRUE)
  ->set('js.preprocess', TRUE)
  ->set('cache.page.max_age', 900)
  ->save();
echo "Performance: CSS/JS aggregation on, page cache max-age 900s\n";
