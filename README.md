# Checkout Kata

A PHP implementation of [CodeKata 09: Back to the Checkout](http://codekata.com/kata/kata09-back-to-the-checkout/).

Items can be scanned in any order. The checkout calculates a running total using the pricing rules supplied when it is created, including quantity-based special prices.

| SKU | Unit price | Special price |
| --- | ---: | --- |
| A | 50 | 3 for 130 |
| B | 30 | 2 for 45 |
| C | 20 | — |
| D | 15 | — |

All prices and totals use integer minor units, such as cents. For example, 130 represents €1.30 when using euros. No floating-point arithmetic is needed.

## Requirements

- PHP 8.2 or newer
- Composer
- Xdebug, optional for coverage reports

## Installation and tests

From the project directory:

```sh
composer install
composer test
```

To generate a coverage report with Xdebug installed:

```sh
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text
```

## Usage

Run this example from a PHP file in the project root:

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Checkout\Checkout;
use App\Pricing\BundlePricingRule;
use App\Pricing\UnitPricingRule;

$checkout = new Checkout([
    'A' => new BundlePricingRule(unitPrice: 50, bundleQuantity: 3, bundlePrice: 130),
    'B' => new BundlePricingRule(unitPrice: 30, bundleQuantity: 2, bundlePrice: 45),
    'C' => new UnitPricingRule(unitPrice: 20),
    'D' => new UnitPricingRule(unitPrice: 15),
]);

foreach (['A', 'B', 'A'] as $sku) {
    $checkout->scan($sku);
}

echo $checkout->total() . PHP_EOL; // 130

$checkout->scan('A');
echo $checkout->total() . PHP_EOL; // 160: three As for 130, plus B for 30

$checkout->scan('B');
echo $checkout->total() . PHP_EOL; // 175: three As for 130, two Bs for 45
```

Each call to `scan()` accepts one complete SKU, so identifiers such as `APPLE` work as well as `A`.

## Design decisions

### Pricing strategies

`PricingRuleInterface` defines `calculate(int $quantity): int`. The two implementations are:

- `UnitPricingRule`: multiplies quantity by unit price.
- `BundlePricingRule`: applies the bundle price to each complete bundle and unit pricing to the remaining items.

This is the Strategy pattern: checkout delegates pricing to interchangeable objects. New per-product pricing behavior can be introduced without changing checkout code.

### Constructor injection

The caller supplies the SKU-to-rule map. Product names and prices are not hard-coded into checkout. Separate transactions can use different price lists. Built-in pricing rules are immutable, and each checkout keeps its own basket.

Custom pricing strategies should also remain immutable and return the same result for the same quantity, so repeated total calculations stay consistent.

### Quantity-based state

Checkout stores a count for each SKU and recalculates the total when requested. This matters because scanning the third A changes the price of the group from 150 to 130. Keeping only a running sum of unit prices would miss the discount.

Scanning takes O(1) time. Calculating a total takes O(k) time for k distinct scanned SKUs, assuming constant-time pricing strategies. Basket storage takes O(k) space.

### Scope

Plain PHP classes keep the solution small and independently testable. The kata does not require a framework, database, HTTP API, or dependency injection container.

## Behavior and assumptions

- An empty checkout totals zero.
- Repeated calls to `total()` do not change the basket.
- An unknown SKU throws `UnknownSkuException` before changing the basket.
- A configured value that does not implement `PricingRuleInterface` is rejected at construction.
- Prices must be non-negative and bundle quantities must be positive. Zero prices are allowed.
- The current pricing rules return zero for zero or negative quantities. Checkout itself only supplies positive scanned quantities.
- Bundles repeat. Seven As cost 310: two bundles at 130 and one item at 50.
- Bundle prices apply as configured; the implementation does not search for cheaper competing offers.
- One pricing rule applies per SKU. Cross-product discounts would require a design that can inspect the whole basket.
- Currency conversion, taxes, returns, persistence, and arithmetic beyond PHP's integer range are outside this implementation's scope.

## Tests

PHPUnit tests cover unit and bundle pricing, invalid price configuration, empty and mixed baskets, bundle boundaries, incremental totals, repeated reads, unknown SKUs, independent transactions, alternative pricing strategies, and multi-character SKUs.

Checkout fixtures use explicit SKU arrays to avoid assuming that every identifier is a single character.

## Project structure

```text
src/
  Checkout/
    Checkout.php
  Exception/
    UnknownSkuException.php
  Pricing/
    PricingRuleInterface.php
    UnitPricingRule.php
    BundlePricingRule.php
tests/
  CheckoutTest.php
  UnitPricingRuleTest.php
  BundlePricingRuleTest.php
```
