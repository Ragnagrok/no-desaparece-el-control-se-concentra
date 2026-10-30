#!/usr/bin/env bash
# Replays the article on a copy of the code:
#   1. the law test finds lost cents with negative amounts, and shrinks to one cent in two parts;
#   2. with a generator of positive amounts only, the same buggy code passes every time;
#   3. after the two-line fix, the law holds with negative amounts too.
set -euo pipefail
cd "$(dirname "$0")"

work=$(mktemp -d)
trap 'rm -rf "$work"' EXIT
cp -r src test examples "$work/"

failures=0
expect() {
  local label=$1 text=$2; shift 2
  local out
  out=$(php "$@" 2>&1 || true)
  if grep -qF -- "$text" <<<"$out"; then
    echo "ok   $label: $text"
  else
    echo "FAIL $label: expected '$text', got:"; echo "$out"; failures=1
  fi
}

expect "buggy, all amounts"    'Smallest: -1 allocated in [1, 1] sums 0' "$work/test/law.php"
expect "buggy, positive only"  'OK: the law held in 10000 cases'         "$work/test/law.php" --positive-only
patch -s -p1 -d "$work" < fixes/negative-amounts.diff
expect "fixed, all amounts"    'OK: the law held in 10000 cases'         "$work/test/law.php"
expect "commission example"    'after:  commission 351 cents, to merchant 9650 cents' "$work/examples/commission.php"
exit $failures
