<?php

declare(strict_types=1);

namespace Drupal\odo_core;

/**
 * Parses and validates World Bank API v2 indicator responses.
 *
 * Kept free of Drupal dependencies so it can be unit tested in isolation.
 *
 * Expected payload shape (format=json):
 * @code
 * [
 *   {"page": 1, "pages": 1, "total": 25, ...},
 *   [
 *     {"indicator": {"id": "SP.POP.TOTL", "value": "Population, total"},
 *      "country": {"id": "LB", "value": "Lebanon"},
 *      "countryiso3code": "LBN", "date": "2023", "value": 5353930}
 *   ]
 * ]
 * @endcode
 *
 * On error the API returns HTTP 200 with
 * `[{"message": [{"id": "120", "value": "Invalid value"}]}]`, so the shape
 * must be checked explicitly rather than relying on the status code.
 */
final class WorldBankParser {

  /**
   * Earliest and latest accepted years; anything outside is bad data.
   */
  private const MIN_YEAR = 1960;
  private const MAX_YEAR = 2100;

  /**
   * Parses a decoded API response.
   *
   * @param mixed $decoded
   *   The json_decode()d response body (associative arrays).
   *
   * @return array{indicator_name: string, country_name: string, points: array<int, float>, skipped: int}
   *   Valid points keyed by year (ascending), plus a count of skipped rows.
   *
   * @throws \InvalidArgumentException
   *   If the payload is an API error or does not have the expected shape.
   */
  public static function parse(mixed $decoded): array {
    if (!is_array($decoded) || !array_is_list($decoded) || $decoded === []) {
      throw new \InvalidArgumentException('Response is not a JSON array.');
    }
    if (isset($decoded[0]['message'])) {
      $message = $decoded[0]['message'][0]['value'] ?? 'unknown error';
      throw new \InvalidArgumentException('World Bank API error: ' . $message);
    }
    if (!isset($decoded[1]) || !is_array($decoded[1]) || $decoded[1] === []) {
      throw new \InvalidArgumentException('Response contains no data rows.');
    }

    $indicator_name = '';
    $country_name = '';
    $points = [];
    $skipped = 0;

    foreach ($decoded[1] as $row) {
      if (!is_array($row)) {
        $skipped++;
        continue;
      }
      $indicator_name = $indicator_name ?: (string) ($row['indicator']['value'] ?? '');
      $country_name = $country_name ?: (string) ($row['country']['value'] ?? '');

      $year = filter_var($row['date'] ?? NULL, FILTER_VALIDATE_INT);
      $value = $row['value'] ?? NULL;

      // The API reports missing years as null; also reject non-numeric values
      // and out-of-range years instead of storing them.
      if ($year === FALSE || $year < self::MIN_YEAR || $year > self::MAX_YEAR || !is_numeric($value)) {
        $skipped++;
        continue;
      }
      $points[$year] = (float) $value;
    }

    if ($indicator_name === '' || $country_name === '') {
      throw new \InvalidArgumentException('Response rows are missing indicator or country names.');
    }

    ksort($points);

    return [
      'indicator_name' => $indicator_name,
      'country_name' => $country_name,
      'points' => $points,
      'skipped' => $skipped,
    ];
  }

}
