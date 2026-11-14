<?php

declare(strict_types=1);

namespace Drupal\odo_core\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Lists the most recently updated datasets.
 */
#[Block(
  id: 'odo_latest_datasets',
  admin_label: new TranslatableMarkup('Latest datasets'),
  category: new TranslatableMarkup('Open Data Observatory'),
)]
final class LatestDatasetsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  private const LIMIT = 5;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $storage = $this->entityTypeManager->getStorage('node');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'dataset')
      ->condition('status', 1)
      ->sort('changed', 'DESC')
      ->range(0, self::LIMIT)
      ->execute();

    $items = [];
    foreach ($storage->loadMultiple($ids) as $node) {
      $items[] = [
        '#type' => 'link',
        '#title' => $node->label(),
        '#url' => $node->toUrl(),
      ];
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#empty' => $this->t('No datasets yet.'),
      // Rebuild when any node changes; vary by permissions for access checks.
      '#cache' => [
        'tags' => ['node_list:dataset'],
        'contexts' => ['user.permissions'],
      ],
    ];
  }

}
