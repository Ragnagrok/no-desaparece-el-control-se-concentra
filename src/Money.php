<?php

declare(strict_types=1);

enum Currency
{
    case ARS;
    case USD;
}

final readonly class Money
{
    private function __construct(public int $cents, public Currency $currency) {}

    public static function of(int $cents, Currency $currency): self
    {
        return new self($cents, $currency);
    }

    public function plus(Money $other): self
    {
        if ($other->currency !== $this->currency) {
            throw new InvalidArgumentException("Can't add different currencies");
        }
        return new self($this->cents + $other->cents, $this->currency);
    }

    /**
     * Splits the amount in proportion to $weights.
     * Law: the parts add up to the original amount. No cent is created or lost.
     */
    public function allocate(array $weights): array
    {
        $total = array_sum($weights);
        $parts = array_map(fn ($w) => intdiv($this->cents * $w, $total), $weights);
        // What was lost to rounding is handed out one cent at a time.
        $left = $this->cents - array_sum($parts);
        for ($i = 0; $i < $left; $i++) {
            $parts[$i]++;
        }
        return array_map(fn ($c) => new self($c, $this->currency), $parts);
    }
}
