<?php

declare(strict_types=1);

namespace App\Pricing;

final readonly class BundlePricingRule implements PricingRuleInterface
{
    public function __construct(
        private int $unitPrice,
        private int $bundleQuantity,
        private int $bundlePrice,
    ) {
        if ($unitPrice < 0) {
            throw new \InvalidArgumentException(
                'Unit price cannot be negative.'
            );
        }

        if ($bundleQuantity <= 0) {
            throw new \InvalidArgumentException(
                'Bundle quantity must be greater than zero.'
            );
        }

        if ($bundlePrice < 0) {
            throw new \InvalidArgumentException(
                'Bundle price cannot be negative.'
            );
        }
    }

    public function calculate(int $quantity): int
    {
        if ($quantity <= 0) {
            return 0;
        }

        $bundles = intdiv($quantity, $this->bundleQuantity);
        $remainingItems = $quantity % $this->bundleQuantity;

        return ($bundles * $this->bundlePrice)
            + ($remainingItems * $this->unitPrice);
    }
}
