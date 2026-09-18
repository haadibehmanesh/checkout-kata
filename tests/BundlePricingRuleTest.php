<?php

declare(strict_types=1);

namespace Tests;

use App\Pricing\BundlePricingRule;
use PHPUnit\Framework\TestCase;

final class BundlePricingRuleTest extends TestCase
{
    public function testCalculatesBundlePricing(): void
    {
        $rule = new BundlePricingRule(
            unitPrice: 50,
            bundleQuantity: 3,
            bundlePrice: 130,
        );

        self::assertSame(0, $rule->calculate(0));
        self::assertSame(50, $rule->calculate(1));
        self::assertSame(100, $rule->calculate(2));
        self::assertSame(130, $rule->calculate(3));
        self::assertSame(180, $rule->calculate(4));
        self::assertSame(260, $rule->calculate(6));
    }

    public function testRejectsNegativeUnitPrice(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new BundlePricingRule(
            unitPrice: -1,
            bundleQuantity: 3,
            bundlePrice: 130,
        );
    }

    public function testRejectsInvalidBundleQuantity(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new BundlePricingRule(
            unitPrice: 50,
            bundleQuantity: 0,
            bundlePrice: 130,
        );
    }

    public function testRejectsNegativeBundlePrice(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new BundlePricingRule(
            unitPrice: 50,
            bundleQuantity: 3,
            bundlePrice: -1,
        );
    }
}
