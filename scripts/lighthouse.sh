#!/usr/bin/env bash
# Runs Lighthouse (mobile, default simulated throttling) on key page types.
# Saves the raw JSON and a summary with every failing audit.
# Usage: scripts/lighthouse.sh <label>   e.g. baseline, after
#
# Runs on the server with /opt/node and /opt/lighthouse (audit tooling only;
# not served to visitors). --no-sandbox is needed because Ubuntu 24.04
# AppArmor blocks Chrome's user-namespace sandbox for normal users; it is
# only used here to audit this site's own pages.
set -u
LABEL="${1:?label required}"
BASE="https://observatory.raiuz.site"
OUT="docs/reports/lighthouse/$LABEL"
LH=/opt/lighthouse/node_modules/.bin/lighthouse
export PATH=/opt/node/bin:$PATH
mkdir -p "$OUT"
: > "$OUT/summary.txt"
for page in "home:/" "datasets:/datasets" "dataset:/datasets/lbn/sp-pop-totl" "report:/reports"; do
  name="${page%%:*}"; path="${page#*:}"
  "$LH" "$BASE$path" --quiet --output=json --output-path="$OUT/$name.json" \
    --chrome-flags="--headless=new --no-sandbox --disable-gpu" \
    --only-categories=performance,accessibility,best-practices,seo >/dev/null 2>&1
  php -r '
    $r = json_decode(@file_get_contents($argv[1]), true);
    $r = $r["lighthouseResult"] ?? $r;
    if (!isset($r["categories"])) { echo str_pad($argv[2], 9) . " | ERROR no result" . PHP_EOL; exit; }
    $c = $r["categories"]; $a = $r["audits"];
    $s = fn($k) => (int) round($c[$k]["score"] * 100);
    echo implode(" | ", [str_pad($argv[2], 9), "perf " . $s("performance"), "a11y " . $s("accessibility"),
      "bp " . $s("best-practices"), "seo " . $s("seo"),
      "LCP " . $a["largest-contentful-paint"]["displayValue"], "CLS " . $a["cumulative-layout-shift"]["displayValue"],
      "TBT " . $a["total-blocking-time"]["displayValue"]]) . PHP_EOL;
    foreach ($a as $id => $audit) {
      if (($audit["score"] ?? null) !== null && $audit["score"] < 0.9 && in_array($audit["scoreDisplayMode"] ?? "", ["binary", "numeric", "metricSavings"], true)) {
        echo "    - " . $id . ": " . $audit["title"] . PHP_EOL;
      }
    }
  ' "$OUT/$name.json" "$name" >> "$OUT/summary.txt"
done
echo DONE >> "$OUT/summary.txt"
