<?php

declare(strict_types=1);

namespace Tests;

use App\Checkout\Checkout;
use App\Exception\UnknownSkuException;
use App\Pricing\BundlePricingRule;
use App\Pricing\PricingRuleInterface;
use App\Pricing\UnitPricingRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CheckoutTest extends TestCase
{
    private function createCheckout(): Checkout
    {
        return new Checkout([
            'A' => new BundlePricingRule(unitPrice: 50, bundleQuantity: 3, bundlePrice: 130),
            'B' => new BundlePricingRule(unitPrice: 30, bundleQuantity: 2, bundlePrice: 45),
            'C' => new UnitPricingRule(unitPrice: 20),
            'D' => new UnitPricingRule(unitPrice: 15),
        ]);
    }

    /** @param list<string> $skus */
    #[DataProvider('baskets')]
    public function testCalculatesTotal(array $skus, int $expected): void
    {
        $checkout = $this->createCheckout();
        foreach ($skus as $sku) {
            $checkout->scan($sku);
        }
        self::assertSame($expected, $checkout->total());
    }

    /** @return array<string, array{list<string>, int}> */
    public static function baskets(): array
    {
        return [
            'empty' => [[], 0],
            'single' => [['A'], 50],
            'different items' => [['A', 'B'], 80],
            'all products' => [['C', 'D', 'B', 'A'], 115],
            'below bundle' => [['A', 'A'], 100],
            'exact bundle' => [['A', 'A', 'A'], 130],
            'one remainder' => [['A', 'A', 'A', 'A'], 180],
            'two remainders' => [['A', 'A', 'A', 'A', 'A'], 230],
            'two bundles' => [['A', 'A', 'A', 'A', 'A', 'A'], 260],
            'two bundles plus remainder' => [['A', 'A', 'A', 'A', 'A', 'A', 'A'], 310],
            'B bundle' => [['B', 'B'], 45],
            'mixed bundle' => [['A', 'A', 'A', 'B'], 160],
            'both bundles' => [['A', 'A', 'A', 'B', 'B'], 175],
            'mixed basket' => [['A', 'A', 'A', 'B', 'B', 'D'], 190],
            'interleaved basket' => [['D', 'A', 'B', 'A', 'B', 'A'], 190],
        ];
    }

    public function testTotalUpdatesAfterEachScan(): void
    {
        $checkout = $this->createCheckout();
        self::assertSame(0, $checkout->total());

        $steps = [
            ['sku' => 'A', 'total' => 50],
            ['sku' => 'B', 'total' => 80],
            ['sku' => 'A', 'total' => 130],
            ['sku' => 'A', 'total' => 160],
            ['sku' => 'B', 'total' => 175],
        ];

        foreach ($steps as ['sku' => $sku, 'total' => $expected]) {
            $checkout->scan($sku);
            self::assertSame($expected, $checkout->total());
        }
    }

    public function testReadingTotalDoesNotChangeTheBasket(): void
    {
        $checkout = $this->createCheckout();
        $checkout->scan('A');

        self::assertSame(50, $checkout->total());
        self::assertSame(50, $checkout->total());

        $checkout->scan('A');
        $checkout->scan('A');
        self::assertSame(130, $checkout->total());
    }

    public function testSupportsMultiCharacterSkus(): void
    {
        $checkout = new Checkout([
            'APPLE' => new BundlePricingRule(unitPrice: 50, bundleQuantity: 3, bundlePrice: 130),
            'BREAD' => new UnitPricingRule(unitPrice: 20),
        ]);
        $skus = ['APPLE', 'BREAD', 'APPLE', 'APPLE'];

        foreach ($skus as $sku) {
            $checkout->scan($sku);
        }

        self::assertSame(150, $checkout->total());
    }

    public function testUnknownScanDoesNotChangeTheBasket(): void
    {
        $checkout = $this->createCheckout();
        $checkout->scan('A');
        try {
            $checkout->scan('X');
            self::fail('Expected an unknown SKU exception.');
        } catch (UnknownSkuException $exception) {
            self::assertSame('Unknown SKU: X', $exception->getMessage());
        }
        self::assertSame(50, $checkout->total());
        $checkout->scan('B');
        self::assertSame(80, $checkout->total());
    }

    public function testTransactionsHaveIndependentBasketsAndPriceLists(): void
    {
        $rules = ['A' => new UnitPricingRule(50)];
        $first = new Checkout($rules);
        $rules['A'] = new UnitPricingRule(60);
        $second = new Checkout($rules);
        $first->scan('A');
        self::assertSame(0, $second->total());
        $second->scan('A');
        self::assertSame(50, $first->total());
        self::assertSame(60, $second->total());
    }

    public function testAcceptsAnotherPricingStrategy(): void
    {
        $rule = new class implements PricingRuleInterface {
            public function calculate(int $quantity): int
            {
                return $quantity * 7;
            }
        };
        $checkout = new Checkout(['SPECIAL' => $rule]);
        $checkout->scan('SPECIAL');
        $checkout->scan('SPECIAL');
        self::assertSame(14, $checkout->total());
    }

    public function testRejectsInvalidPricingRule(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        /** @noinspection PhpParamsInspection */
        new Checkout(['A' => 50]);
    }

    public function testEmptyCatalogRejectsScans(): void
    {
        $checkout = new Checkout([]);
        self::assertSame(0, $checkout->total());
        $this->expectException(UnknownSkuException::class);
        $checkout->scan('A');
    }
}
