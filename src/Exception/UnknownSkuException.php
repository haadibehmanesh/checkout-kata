<?php

declare(strict_types=1);

namespace App\Exception;

use InvalidArgumentException;

final class UnknownSkuException extends InvalidArgumentException
{
    public static function forSku(string $sku): self
    {
        return new self(sprintf('Unknown SKU: %s', $sku));
    }
}
