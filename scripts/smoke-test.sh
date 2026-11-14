#!/usr/bin/env bash
# Smoke test: fails (exit 1) if any key page does not return HTTP 200.
# Run after every deploy or permission change:  scripts/smoke-test.sh [base-url]
set -u
BASE="${1:-https://observatory.raiuz.site}"
PAGES=("/" "/reports" "/datasets" "/news" "/search?keys=Lebanon" "/user/login" "/sitemap.xml")
fail=0
for p in "${PAGES[@]}"; do
  code=$(curl -sS -o /dev/null -m 15 -w "%{http_code}" "$BASE$p")
  if [ "$code" = "200" ]; then echo "ok   $code $p"; else echo "FAIL $code $p"; fail=1; fi
done
exit $fail
