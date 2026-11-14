# odo_core module

Custom module in `web/modules/custom/odo_core`. Imports World Bank indicators as Dataset
nodes and provides a "Latest datasets" block.

## Files

| File | Role |
|---|---|
| `odo_core.info.yml` | Module definition; depends on node, taxonomy, link |
| `odo_core.services.yml` | `odo_core.worldbank_importer` service and `logger.channel.odo_core` |
| `src/WorldBankParser.php` | Validates and parses API responses (no Drupal dependencies) |
| `src/WorldBankImporter.php` | Fetches, retries and saves Dataset nodes |
| `src/Drush/Commands/OdoCommands.php` | `drush odo:import-worldbank` |
| `src/Plugin/Block/LatestDatasetsBlock.php` | Block listing the 5 most recently updated datasets |
| `tests/src/Unit/WorldBankParserTest.php` | 9 unit tests |

## Command

```bash
drush odo:import-worldbank                          # all 8 countries x 5 indicators
drush odo:import-worldbank --countries=LBN,DEU      # subset
drush odo:import-worldbank --indicators=SP.POP.TOTL
```

Output is a table: created, updated, failed, skipped data points. Exit code is non-zero
only if every series failed. Unknown codes are rejected before any request is made.

Details of failures: `drush watchdog:show --type=odo_core`.

## Design decisions

**Parser separated from importer.** `WorldBankParser` is a pure function over decoded JSON,
so the bad-data handling is unit tested without Drupal or network access.

**Payload shape is checked, not just the status code.** The World Bank API returns errors as
HTTP 200 with `[{"message": [...]}]`. The parser rejects that, empty data, rows without
indicator or country names, and non-list payloads.

**Bad values are skipped, not stored.** Null values (the API's "no data for this year"),
non-numeric values and years outside 1960 to 2100 are counted in `skipped` and left out.

**Retries with backoff.** On 14 Nov 2026 the API often accepted connections and sent nothing.
Each request has a 5-second connect timeout and 10-second total timeout, and up to 3
attempts with 1 s and 2 s pauses. Only network errors (`TransferException`) are retried.
One series failing never stops the others.

**Idempotent upsert.** Nodes are matched on indicator code and country code, so re-running
updates values in place. The first run on 14 Nov imported 27 of 40 series; the re-run filled
the remaining 13 with no duplicates.

**Allow-listed inputs.** Countries and indicators come from constants in `WorldBankImporter`.
Values are also URL-encoded before use.

**Block caching.** `LatestDatasetsBlock` uses the `node_list:dataset` cache tag and the
`user.permissions` context, so it refreshes when datasets change and respects access checks.

## Tests

```bash
vendor/bin/phpunit web/modules/custom/odo_core/tests/src/Unit
```

| Test | Checks |
|---|---|
| `testParsesValidRowsInYearOrder` | Names extracted; points sorted by year; numeric strings accepted |
| `testSkipsMissingNonNumericAndOutOfRangeValues` | null, "n/a", invalid year, year 1800 and a non-array row skipped (5) |
| `testApiErrorPayloadThrows` | HTTP 200 error payload raises a clear exception |
| `testMalformedPayloadThrows` (6 cases) | Not an array, empty, object, metadata only, no rows, rows without names |

Coding standards: `vendor/bin/phpcs --standard=Drupal,DrupalPractice web/modules/custom` (clean).

## Adding an indicator or country

1. Add the code to `INDICATORS` (with its Topic term name) or `COUNTRIES` (with its Region
   term name) in `WorldBankImporter`.
2. Run the importer, then `scripts/content.php` to regenerate reports.
3. Update `docs/content-model.md`.
