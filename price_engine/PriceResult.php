<?php
declare(strict_types=1);

class PriceResult
{
    public string $productId = '';
    public string $priceListId = '';

    // COSTO
    public float $costNet = 0.00;
    public float $costAdjusted = 0.00;

    // VENTA
    public float $saleNet = 0.00;
    public float $saleVat = 0.00;
    public float $saleGross = 0.00;

    // OVERRIDE
    public bool $hasOverride = false;
    public ?float $overridePrice = null;

    // CAMPAÑAS
    public array $campaigns = [];

    // RESULTADO
    public float $finalPrice = 0.00;

    // TRACE
    public ?PriceTrace $trace = null;

    // DATOS AUXILIARES
    public array $context = [];
}