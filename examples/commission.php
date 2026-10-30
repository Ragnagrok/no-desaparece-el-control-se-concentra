<?php
// The same commission, before and after the piece.

declare(strict_types=1);

require __DIR__ . '/../src/Money.php';

// Before: a float, a rounding, and a subtraction, all of which a reviewer has to check.
$total = 100.01;
$commission = round($total * 0.035, 2);
$toMerchant = $total - $commission;
printf("before: commission %.2f, to merchant %.2f\n", $commission, $toMerchant);

// After: the only thing left to review is the intent (3.5%, and who gets the spare cent).
[$commission, $toMerchant] = Money::of(10001, Currency::ARS)->allocate([35, 965]);
printf("after:  commission %d cents, to merchant %d cents\n", $commission->cents, $toMerchant->cents);
