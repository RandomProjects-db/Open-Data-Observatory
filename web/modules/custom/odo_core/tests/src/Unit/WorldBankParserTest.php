<?php

declare(strict_types=1);

namespace Drupal\Tests\odo_core\Unit;

use Drupal\odo_core\WorldBankParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests World Bank response parsing, especially bad-data handling.
 */
#[CoversClass(WorldBankParser::class)]
final class WorldBankParserTest extends TestCase {

  /**
   * Builds one API row.
   */
  private static function row(mixed $date, mixed $value): array {
    return [
      'indicator' => ['id' => 'SP.POP.TOTL', 'value' => 'Population, total'],
      'country' => ['id' => 'LB', 'value' => 'Lebanon'],
      'countryiso3code' => 'LBN',
      'date' => $date,
      'value' => $value,
    ];
  }

  /**
   * Valid rows are parsed and sorted by year.
   */
  public function testParsesValidRowsInYearOrder(): void {
    $result = WorldBankParser::parse([
      ['page' => 1],
      [self::row('2022', 5400000), self::row('2020', 5600000), self::row('2021', '5500000.5')],
    ]);

    $this->assertSame('Population, total', $result['indicator_name']);
    $this->assertSame('Lebanon', $result['country_name']);
    $this->assertSame([2020 => 5600000.0, 2021 => 5500000.5, 2022 => 5400000.0], $result['points']);
    $this->assertSame(0, $result['skipped']);
  }

  /**
   * Null, non-numeric, invalid-year and non-array rows are skipped.
   */
  public function testSkipsMissingNonNumericAndOutOfRangeValues(): void {
    $result = WorldBankParser::parse([
      ['page' => 1],
      [
        self::row('2023', 100),
        // The API reports missing years as null.
        self::row('2022', NULL),
        self::row('2021', 'n/a'),
        self::row('abcd', 50),
        self::row('1800', 50),
        'not-a-row',
      ],
    ]);

    $this->assertSame([2023 => 100.0], $result['points']);
    $this->assertSame(5, $result['skipped']);
  }

  /**
   * An API error payload (HTTP 200 with a message) throws.
   */
  public function testApiErrorPayloadThrows(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('World Bank API error: The indicator was not found');
    WorldBankParser::parse([
      ['message' => [['id' => '175', 'key' => 'Invalid format', 'value' => 'The indicator was not found']]],
    ]);
  }

  /**
   * Malformed payloads that must be rejected.
   */
  public static function malformedProvider(): array {
    return [
      'not an array' => ['oops'],
      'empty array' => [[]],
      'object, not list' => [['a' => 1]],
      'metadata only' => [[['page' => 1]]],
      'empty data rows' => [[['page' => 1], []]],
      'rows without names' => [[['page' => 1], [['date' => '2020', 'value' => 1]]]],
    ];
  }

  /**
   * Malformed payloads throw instead of importing partial data.
   */
  #[DataProvider('malformedProvider')]
  public function testMalformedPayloadThrows(mixed $payload): void {
    $this->expectException(\InvalidArgumentException::class);
    WorldBankParser::parse($payload);
  }

}
