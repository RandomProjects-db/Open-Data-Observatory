<?php

declare(strict_types=1);

namespace Drupal\odo_core\Drush\Commands;

use Drupal\odo_core\WorldBankImporter;
use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Drush commands for the Open Data Observatory.
 */
final class OdoCommands extends DrushCommands {

  use AutowireTrait;

  public function __construct(
    #[Autowire(service: 'odo_core.worldbank_importer')]
    private readonly WorldBankImporter $importer,
  ) {
    parent::__construct();
  }

  /**
   * Imports World Bank indicators as Dataset nodes.
   */
  #[CLI\Command(name: 'odo:import-worldbank', aliases: ['odo-wb'])]
  #[CLI\Option(name: 'countries', description: 'Comma-separated ISO3 codes. Defaults to all configured countries.')]
  #[CLI\Option(name: 'indicators', description: 'Comma-separated indicator codes. Defaults to all configured indicators.')]
  #[CLI\Usage(name: 'drush odo:import-worldbank --countries=LBN,DEU', description: 'Import all indicators for Lebanon and Germany.')]
  public function importWorldBank(array $options = ['countries' => '', 'indicators' => '']): int {
    $countries = $this->validated($options['countries'], WorldBankImporter::COUNTRIES, 'country');
    $indicators = $this->validated($options['indicators'], WorldBankImporter::INDICATORS, 'indicator');
    if ($countries === NULL || $indicators === NULL) {
      return self::EXIT_FAILURE;
    }

    $totals = $this->importer->import($countries, $indicators);
    $this->io()->table(['Created', 'Updated', 'Failed', 'Skipped data points'], [array_values($totals)]);

    if ($totals['failed'] > 0) {
      $this->logger()->warning('Some series failed. Details: drush watchdog:show --type=odo_core');
    }
    return $totals['failed'] > 0 && $totals['created'] + $totals['updated'] === 0 ? self::EXIT_FAILURE : self::EXIT_SUCCESS;
  }

  /**
   * Validates a comma-separated option against an allow-list.
   *
   * @return string[]|null
   *   The codes, all defaults if empty, or NULL if any code is unknown.
   */
  private function validated(string $raw, array $allowed, string $label): ?array {
    if (trim($raw) === '') {
      return array_keys($allowed);
    }
    $codes = array_map(fn (string $c): string => strtoupper(trim($c)), explode(',', $raw));
    $unknown = array_diff($codes, array_keys($allowed));
    if ($unknown) {
      $this->logger()->error(sprintf('Unknown %s code(s): %s. Allowed: %s', $label, implode(', ', $unknown), implode(', ', array_keys($allowed))));
      return NULL;
    }
    return $codes;
  }

}
