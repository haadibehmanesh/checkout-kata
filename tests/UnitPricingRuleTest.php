<?php

declare(strict_types=1);

namespace Tests;

use App\Pricing\UnitPricingRule;
use PHPUnit\Framework\TestCase;

final class UnitPricingRuleTest extends TestCase
{
    public function testCalculatesPriceForQuantity(): void
    {
        $rule = new UnitPricingRule(50);

        self::assertSame(0, $rule->calculate(0));
        self::assertSame(50, $rule->calculate(1));
        self::assertSame(150, $rule->calculate(3));
    }

    public function testRejectsNegativeUnitPrice(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new UnitPricingRule(-1);
    }
}
