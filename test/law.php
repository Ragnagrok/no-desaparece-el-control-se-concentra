<?php
// The law, as a test: ten thousand random amounts and splits.
//
//   php test/law.php                 amounts between -1,000,000 and 1,000,000
//   php test/law.php --positive-only amounts between 0 and 1,000,000
//
// Exits with 1 and the smallest failing case it can find, or 0 if the law held.

declare(strict_types=1);

require __DIR__ . '/../src/Money.php';

$min = in_array('--positive-only', $argv, true) ? 0 : -1_000_000;

function holds(int $cents, array $weights): bool
{
    $amount = Money::of($cents, Currency::ARS);
    $sum = array_reduce($amount->allocate($weights), fn ($a, $b) => $a->plus($b), Money::of(0, Currency::ARS));
    return $sum == $amount;
}

function sumOf(int $cents, array $weights): int
{
    $parts = Money::of($cents, Currency::ARS)->allocate($weights);
    return array_sum(array_map(fn ($m) => $m->cents, $parts));
}

/** Smaller cases to try: the amount closer to zero, fewer or lighter weights. */
function candidates(int $cents, array $weights): array
{
    $out = [[intdiv($cents, 2), $weights], [$cents - ($cents <=> 0), $weights]];
    foreach ($weights as $i => $w) {
        if (count($weights) > 1) {
            $out[] = [$cents, array_values(array_diff_key($weights, [$i => 0]))];
        }
        if ($w > 1) {
            $out[] = [$cents, array_replace($weights, [$i => intdiv($w, 2)])];
            $out[] = [$cents, array_replace($weights, [$i => $w - 1])];
        }
    }
    return array_filter($out, fn ($c) => $c !== [$cents, $weights]);
}

/** Shrinking: keep taking the first smaller case that still fails. */
function shrink(int $cents, array $weights): array
{
    do {
        $progress = false;
        foreach (candidates($cents, $weights) as [$c, $w]) {
            if (!holds($c, $w)) {
                [$cents, $weights, $progress] = [$c, $w, true];
                break;
            }
        }
    } while ($progress);
    return [$cents, $weights];
}

for ($i = 0; $i < 10_000; $i++) {
    $cents = random_int($min, 1_000_000);
    $weights = array_map(fn () => random_int(1, 100), range(1, random_int(1, 6)));
    if (!holds($cents, $weights)) {
        printf("Failure: %d allocated in [%s] sums %d\n", $cents, implode(', ', $weights), sumOf($cents, $weights));
        [$cents, $weights] = shrink($cents, $weights);
        printf("Smallest: %d allocated in [%s] sums %d\n", $cents, implode(', ', $weights), sumOf($cents, $weights));
        exit(1);
    }
}
echo "OK: the law held in 10000 cases\n";
