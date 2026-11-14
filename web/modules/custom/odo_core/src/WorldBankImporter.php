<?php

declare(strict_types=1);

namespace Drupal\odo_core;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\TransferException;
use Psr\Log\LoggerInterface;

/**
 * Imports World Bank indicator series as Dataset nodes.
 *
 * One node per indicator per country. Re-running the import updates existing
 * nodes in place (matched on indicator code + country code), so it is safe to
 * schedule.
 */
final class WorldBankImporter {

  private const API = 'https://api.worldbank.org/v2/country/%s/indicator/%s';

  /**
   * Attempts per request before giving up on a series.
   */
  private const MAX_ATTEMPTS = 3;

  /**
   * Default countries (ISO3) mapped to Region term names.
   */
  public const COUNTRIES = [
    'LBN' => 'Middle East & North Africa',
    'JOR' => 'Middle East & North Africa',
    'EGY' => 'Middle East & North Africa',
    'NGA' => 'Sub-Saharan Africa',
    'KEN' => 'Sub-Saharan Africa',
    'DEU' => 'Europe & Central Asia',
    'IND' => 'South Asia',
    'BRA' => 'Latin America & Caribbean',
  ];

  /**
   * Default indicators mapped to Topic term names.
   */
  public const INDICATORS = [
    'SP.POP.TOTL' => 'Population',
    'NY.GDP.PCAP.CD' => 'Economy',
    'SP.DYN.LE00.IN' => 'Health',
    'EG.ELC.ACCS.ZS' => 'Energy',
    'AG.LND.FRST.ZS' => 'Environment',
  ];

  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Imports every requested indicator for every requested country.
   *
   * @param string[] $countries
   *   ISO3 country codes. Must be keys of self::COUNTRIES.
   * @param string[] $indicators
   *   Indicator codes. Must be keys of self::INDICATORS.
   *
   * @return array{created: int, updated: int, failed: int, skipped_points: int}
   *   Import totals.
   */
  public function import(array $countries, array $indicators): array {
    $totals = ['created' => 0, 'updated' => 0, 'failed' => 0, 'skipped_points' => 0];

    foreach ($countries as $country) {
      foreach ($indicators as $indicator) {
        try {
          $series = WorldBankParser::parse($this->fetch($country, $indicator));
          if ($series['points'] === []) {
            throw new \InvalidArgumentException('No valid data points.');
          }
          $totals[$this->save($country, $indicator, $series) ? 'created' : 'updated']++;
          $totals['skipped_points'] += $series['skipped'];
        }
        catch (\InvalidArgumentException | GuzzleException | \JsonException $e) {
          // One bad series must not stop the rest of the import.
          $totals['failed']++;
          $this->logger->warning('Import failed for @country / @indicator: @message', [
            '@country' => $country,
            '@indicator' => $indicator,
            '@message' => $e->getMessage(),
          ]);
        }
      }
    }

    $this->logger->info('World Bank import finished: @created created, @updated updated, @failed failed.', [
      '@created' => $totals['created'],
      '@updated' => $totals['updated'],
      '@failed' => $totals['failed'],
    ]);

    return $totals;
  }

  /**
   * Fetches and decodes one indicator series, retrying transient failures.
   *
   * The World Bank API intermittently accepts connections and then sends no
   * data, so each request gets short timeouts and up to MAX_ATTEMPTS tries
   * with linear backoff (1s, 2s) before the series is marked as failed.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \JsonException
   */
  private function fetch(string $country, string $indicator): mixed {
    $url = sprintf(self::API, rawurlencode($country), rawurlencode($indicator));
    $attempt = 0;
    while (TRUE) {
      $attempt++;
      try {
        $response = $this->httpClient->request('GET', $url, [
          'query' => ['format' => 'json', 'per_page' => 100, 'date' => '1990:2025'],
          'connect_timeout' => 5,
          'timeout' => 10,
          'headers' => ['Accept' => 'application/json'],
        ]);
        return json_decode((string) $response->getBody(), TRUE, 16, JSON_THROW_ON_ERROR);
      }
      catch (TransferException $e) {
        if ($attempt >= self::MAX_ATTEMPTS) {
          throw $e;
        }
        $this->logger->notice('Retrying @country / @indicator (attempt @n): @message', [
          '@country' => $country,
          '@indicator' => $indicator,
          '@n' => $attempt + 1,
          '@message' => $e->getMessage(),
        ]);
        sleep($attempt);
      }
    }
  }

  /**
   * Creates or updates the Dataset node for one series.
   *
   * @return bool
   *   TRUE if a node was created, FALSE if an existing one was updated.
   */
  private function save(string $country, string $indicator, array $series): bool {
    $storage = $this->entityTypeManager->getStorage('node');
    $existing = $storage->loadByProperties([
      'type' => 'dataset',
      'field_indicator_code' => $indicator,
      'field_country_code' => $country,
    ]);
    /** @var \Drupal\node\NodeInterface $node */
    $node = $existing ? reset($existing) : $storage->create(['type' => 'dataset', 'status' => NodeInterface::PUBLISHED]);
    $is_new = $node->isNew();

    $years = array_keys($series['points']);
    $latest_year = end($years);

    $node->setTitle(sprintf('%s: %s', $series['indicator_name'], $series['country_name']));
    $node->set('field_indicator_code', $indicator);
    $node->set('field_country_code', $country);
    $node->set('field_latest_year', $latest_year);
    $node->set('field_latest_value', $series['points'][$latest_year]);
    $node->set('field_series', json_encode($series['points'], JSON_THROW_ON_ERROR));
    $node->set('field_source_url', ['uri' => sprintf('https://data.worldbank.org/indicator/%s?locations=%s', $indicator, substr($country, 0, 2))]);
    $node->set('body', [
      'value' => sprintf(
        '<p>%s for %s, %d to %d. Source: World Bank Open Data (CC BY 4.0).</p>',
        htmlspecialchars($series['indicator_name']),
        htmlspecialchars($series['country_name']),
        reset($years),
        $latest_year,
      ),
      'format' => 'basic_html',
    ]);
    $node->set('field_region', $this->termId('region', self::COUNTRIES[$country] ?? ''));
    $node->set('field_topic', $this->termId('topic', self::INDICATORS[$indicator] ?? ''));
    $node->save();

    return $is_new;
  }

  /**
   * Looks up a term ID by vocabulary and name.
   */
  private function termId(string $vid, string $name): ?int {
    if ($name === '') {
      return NULL;
    }
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadByProperties(['vid' => $vid, 'name' => $name]);
    return $terms ? (int) reset($terms)->id() : NULL;
  }

}
