<?php
declare(strict_types=1);

class RuleResolver
{
    public function resolve(
        array $context,
        PriceResult $result
    ): PriceResult {
        $inputCost = (float)($context['regular_price'] ?? 0);
        $costType = (string)($context['cost_type'] ?? 'gross');
        $purchaseVatPercent = (float)($context['purchase_vat_percent'] ?? 0);
        $baseMarkupPercent = (float)$context['base_markup_percent'];
        $priceMarkupPercent = (float)$context['price_markup_percent'];
        $adjustmentPercent = (float)$context['adjustment_percent'];
        $saleVatPercent = (float)($context['sale_vat_percent'] ?? 0);

        $costNet = $this->resolveCostNet($inputCost, $costType, $purchaseVatPercent);
        $costGross = $this->resolveCostGross($inputCost, $costNet, $costType, $purchaseVatPercent);

        $costAdjusted = $costNet * (1 + ($baseMarkupPercent / 100));
        $saleNetBeforeAdjustment = $costAdjusted * (1 + ($priceMarkupPercent / 100));
        $saleNet = $saleNetBeforeAdjustment * (1 + ($adjustmentPercent / 100));
        $saleVat = $saleNet * ($saleVatPercent / 100);
        $saleGross = $saleNet + $saleVat;

        $result->costNet      = round($costNet, 2);
        $result->costAdjusted = round($costAdjusted, 2);
        $result->saleNet      = round($saleNet, 2);
        $result->saleVat      = round($saleVat, 2);
        $result->saleGross    = round($saleGross, 2);
        $result->finalPrice = $result->saleGross;

        $result->trace->add('Costo Ingresado', round($inputCost, 2));
        $result->trace->add('Tipo Costo', strtoupper($costType));
        $result->trace->add('IVA Compra %', $purchaseVatPercent);
        $result->trace->add('Costo Bruto Compra', round($costGross, 2));
        $result->trace->add('Costo Neto', $result->costNet);
        $result->trace->add('Recargo Base %', $baseMarkupPercent);
        $result->trace->add('Costo Comercial', $result->costAdjusted);
        $result->trace->add('Margen %', $priceMarkupPercent);
        $result->trace->add('Precio Neto antes de ajuste', round($saleNetBeforeAdjustment, 2));
        $result->trace->add('Ajuste %', $adjustmentPercent);
        $result->trace->add('Precio Neto', $result->saleNet);
        $result->trace->add('IVA Venta %', $saleVatPercent);
        $result->trace->add('IVA Venta', $result->saleVat);
        $result->trace->add('Precio Bruto', $result->saleGross);

        return $result;
    }

    private function resolveCostNet(float $inputCost, string $costType, float $purchaseVatPercent): float
    {
        if ($costType !== 'gross') {
            return $inputCost;
        }

        $multiplier = 1 + ($purchaseVatPercent / 100);

        if ($multiplier <= 0) {
            return $inputCost;
        }

        return $inputCost / $multiplier;
    }

    private function resolveCostGross(float $inputCost, float $costNet, string $costType, float $purchaseVatPercent): float
    {
        if ($costType === 'gross') {
            return $inputCost;
        }

        $multiplier = 1 + ($purchaseVatPercent / 100);

        if ($multiplier <= 0) {
            return $costNet;
        }

        return $costNet * $multiplier;
    }
}