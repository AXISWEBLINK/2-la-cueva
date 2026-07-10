<?php
declare(strict_types=1);

class RoundingResolver
{
    public function resolve(
        array $context,
        PriceResult $result
    ): PriceResult {

        return $result;
    }
}