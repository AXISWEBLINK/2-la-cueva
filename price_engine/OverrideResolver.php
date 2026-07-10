<?php
declare(strict_types=1);

class OverrideResolver
{
    public function resolve(
        array $context,
        PriceResult $result
    ): PriceResult {

        if (
            !empty($context['override_active']) &&
            $context['manual_price_gross'] !== null
        ) {

            $result->hasOverride = true;

            $result->overridePrice =
                (float)$context['manual_price_gross'];

            $result->finalPrice =
                $result->overridePrice;

            $result->trace->add(
                'Override',
                $result->overridePrice
            );
        }

        return $result;
    }
}