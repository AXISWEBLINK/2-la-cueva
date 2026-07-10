<?php
declare(strict_types=1);

class OverrideResolver
{
    public function resolve(
        array $context,
        PriceResult $result
    ): PriceResult {

        return $result;
    }
}