# Control Doesn't Go Away, It Concentrates

Proof of concept for the article *Control Doesn't Go Away, It Concentrates*
([English](https://blog.ragnagrok.com/en/2026-10-30-no-desaparece-el-control-se-concentra) ·
[español](https://blog.ragnagrok.com/es/2026-10-30-no-desaparece-el-control-se-concentra)):
a `Money` type in PHP with an `allocate` operation, a law written in a comment, and a property
test that shows the comment was lying.

The idea in one line: a piece with a law makes review cheaper only if the law is tested, and
only as far as the generated cases reach.

```php
/**
 * Splits the amount in proportion to $weights.
 * Law: the parts add up to the original amount. No cent is created or lost.
 */
public function allocate(array $weights): array
```

## What's here

| File | What it is |
|---|---|
| `src/Money.php` | `Money` (integer cents, currency inside) with `of`, `plus` and `allocate`. It has the article's bug on purpose. |
| `test/law.php` | The law as a test: ten thousand random amounts and splits. With `--positive-only`, the generator only produces amounts of zero or more. |
| `fixes/negative-amounts.diff` | The two-line fix: hand out the leftover cents with their sign. |
| `examples/commission.php` | The same commission computed before (a float and a `round`) and after (`allocate`). |
| `check.sh` | Replays the article on a copy of the code. |

The article is in Spanish and its code uses Spanish names: `Dinero` is `Money`, `Moneda` is
`Currency`, `de` is `of`, `mas` is `plus`, `repartir` is `allocate`, `centavos` is `cents`.

## The story `check.sh` replays

**The law test, all amounts.** It fails right away, and shrinks the failure to the smallest case:

```
Failure: -818331 allocated in [12, 92, 67, 53, 90, 77] sums -818328
Smallest: -1 allocated in [1, 1] sums 0
```

One negative cent split in two equal parts gives zero and zero. PHP's `intdiv` rounds toward
zero, which for negative amounts means *up*, so the leftover is negative and the loop that hands
it out never runs. A cent disappears.

**The law test, positive amounts only.** The same buggy code passes ten thousand cases, every
run. That's the article's third limit: an untested law is a comment, and a law tested only where
it holds is a comment too.

**After the fix.** The law holds in ten thousand cases, negative amounts included.

**The commission.** Before, a reviewer has to think about floats, rounding and whether the two
amounts add up. After, only the intent is left: is it 3.5%, and who keeps the spare cent?
`allocate` gives it to the first part, so for $100.01 the commission comes out as 351 cents,
while the float version rounds it to 3.50.

## Running it

With PHP 8.4, no dependencies:

```sh
./check.sh                        # the whole story, on a temporary copy of the code
php test/law.php                  # or by hand
php test/law.php --positive-only
php examples/commission.php
```

## Why no property-testing library

The article shows the law test as a plain loop, and says that in a real project it would use a
library. This repo keeps the loop so that the code is the article's, and adds a small greedy
shrinker (about thirty lines) so that the failure comes out as the smallest case, as a library
would report it. For a real codebase, use a maintained library instead.

## What this proof of concept doesn't cover

- **`Money` is not the whole vocabulary.** The article's table also lists a transaction boundary,
  idempotency and specifications; those aren't here.
- **"Can't be written" is weaker in PHP.** Here the guarantee comes from the test and from
  `declare(strict_types=1)`, not from a compiler. A static analyzer such as PHPStan would catch
  more before running.
- **Nothing stops a third `allocate`.** The article's answer to vocabulary erosion is a mechanism
  (the pieces in their own directory, changes approved through `CODEOWNERS` and branch
  protection). That is repository configuration, not code, so it isn't reproduced here.

## Further reading

- Lampson, Abadi, Burrows and Wobber (1991), *Authentication in Distributed Systems: Theory and
  Practice*: the definition of the trusted computing base.
- Orlanski et al. (2026), [*SlopCodeBench*](https://arxiv.org/abs/2603.24755): how coding agents'
  code degrades when they extend their own solutions.
- GitHub, [About code owners](https://docs.github.com/en/repositories/managing-your-repositorys-settings-and-features/customizing-your-repository/about-code-owners).
