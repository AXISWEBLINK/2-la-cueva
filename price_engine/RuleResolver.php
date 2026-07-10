<?php
declare(strict_types=1);

class RuleResolver
{
    public function resolve(
        array $context,
        PriceResult $result
    ): PriceResult {

        // 1. Costo del producto
        $costNet = (float)$context['regular_price'];

        // 2. Recargo sobre costo (ej.: IVA del costo si corresponde)
        $costAdjusted = $costNet * (1 + ((float)$context['base_markup_percent'] / 100));

        // 3. Margen comercial
        $saleNet = $costAdjusted * (1 + ((float)$context['price_markup_percent'] / 100));

        // 4. Ajuste
        $saleNet = $saleNet * (1 + ((float)$context['adjustment_percent'] / 100));

        // 5. IVA de venta
        $saleVat = $saleNet * ((float)$context['vat_percent'] / 100);

        // 6. Precio bruto
        $saleGross = $saleNet + $saleVat;

        // Guardar resultado
        $result->costNet      = round($costNet, 2);
        $result->costAdjusted = round($costAdjusted, 2);

        $result->saleNet      = round($saleNet, 2);
        $result->saleVat      = round($saleVat, 2);
        $result->saleGross    = round($saleGross, 2);

        // Por ahora el precio final es el calculado
        $result->finalPrice = $result->saleGross;

        // Traza
        $result->trace->add('Costo Neto', $result->costNet);
        $result->trace->add('Costo Ajustado', $result->costAdjusted);
        $result->trace->add('Precio Neto', $result->saleNet);
        $result->trace->add('IVA Venta', $result->saleVat);
        $result->trace->add('Precio Bruto', $result->saleGross);

        return $result;
    }
}