<?php

declare(strict_types=1);

namespace App\Pricing;

final readonly class UnitPricingRule implements PricingRuleInterface
{
    public function __construct(
        private int $unitPrice,
    ) {
        if ($unitPrice < 0) {
            throw new \InvalidArgumentException(
                'Unit price cannot be negative.'
            );
        }
    }

    public function calculate(int $quantity): int
    {
        if ($quantity <= 0) {
            return 0;
        }

        return $quantity * $this->unitPrice;
    }
}
