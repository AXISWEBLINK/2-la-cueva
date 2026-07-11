<?php
declare(strict_types=1);

class RuleResolver
{
    public function resolve(
        array $context,
        PriceResult $result
    ): PriceResult {
        $costNet = (float)$context['regular_price'];
        $baseMarkupPercent = (float)$context['base_markup_percent'];
        $priceMarkupPercent = (float)$context['price_markup_percent'];
        $adjustmentPercent = (float)$context['adjustment_percent'];
        $vatPercent = (float)$context['vat_percent'];

        $costAdjusted = $costNet * (1 + ($baseMarkupPercent / 100));
        $saleNetBeforeAdjustment = $costAdjusted * (1 + ($priceMarkupPercent / 100));
        $saleNet = $saleNetBeforeAdjustment * (1 + ($adjustmentPercent / 100));
        $saleVat = $saleNet * ($vatPercent / 100);
        $saleGross = $saleNet + $saleVat;

        $result->costNet      = round($costNet, 2);
        $result->costAdjusted = round($costAdjusted, 2);
        $result->saleNet      = round($saleNet, 2);
        $result->saleVat      = round($saleVat, 2);
        $result->saleGross    = round($saleGross, 2);
        $result->finalPrice = $result->saleGross;

        $result->trace->add('Costo Neto', $result->costNet);
        $result->trace->add('Recargo Base %', $baseMarkupPercent);
        $result->trace->add('Costo Comercial', $result->costAdjusted);
        $result->trace->add('Margen %', $priceMarkupPercent);
        $result->trace->add('Precio Neto antes de ajuste', round($saleNetBeforeAdjustment, 2));
        $result->trace->add('Ajuste %', $adjustmentPercent);
        $result->trace->add('Precio Neto', $result->saleNet);
        $result->trace->add('IVA Venta %', $vatPercent);
        $result->trace->add('IVA Venta', $result->saleVat);
        $result->trace->add('Precio Bruto', $result->saleGross);

        return $result;
    }
}