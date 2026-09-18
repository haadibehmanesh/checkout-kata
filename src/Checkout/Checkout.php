<?php

declare(strict_types=1);

namespace App\Checkout;

use App\Exception\UnknownSkuException;
use App\Pricing\PricingRuleInterface;
use InvalidArgumentException;

final class Checkout
{
    /** @var array<string|int, int> */
    private array $quantities = [];

    /** @param array<string|int, PricingRuleInterface> $pricingRules */
    public function __construct(private readonly array $pricingRules)
    {
        foreach ($pricingRules as $sku => $rule) {
            if (!$rule instanceof PricingRuleInterface) {
                throw new InvalidArgumentException(sprintf(
                    'Pricing rule for SKU "%s" must implement %s.',
                    $sku,
                    PricingRuleInterface::class,
                ));
            }
        }
    }

    public function scan(string $sku): void
    {
        if (!isset($this->pricingRules[$sku])) {
            throw UnknownSkuException::forSku($sku);
        }

        $this->quantities[$sku] = ($this->quantities[$sku] ?? 0) + 1;
    }

    public function total(): int
    {
        $total = 0;

        foreach ($this->quantities as $sku => $quantity) {
            $total += $this->pricingRules[$sku]->calculate($quantity);
        }

        return $total;
    }
}
