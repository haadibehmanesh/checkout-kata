<?php

declare(strict_types=1);

namespace App\Pricing;

interface PricingRuleInterface
{
    public function calculate(int $quantity): int;
}
