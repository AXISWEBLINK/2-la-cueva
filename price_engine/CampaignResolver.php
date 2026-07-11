<?php
declare(strict_types=1);

class CampaignResolver
{
    public function resolve(
        array $campaigns,
        PriceResult $result
    ): PriceResult {
        if ($campaigns === []) {
            $result->trace->add('Campañas', 'SIN CAMPAÑAS APLICABLES');
            return $result;
        }

        foreach ($campaigns as $campaign) {
            $priceBeforeCampaign = $result->finalPrice;
            $priceAfterCampaign = $this->applyDiscount($campaign, $priceBeforeCampaign);

            $result->campaigns[] = [
                'id' => $campaign['id'],
                'code' => $campaign['code'],
                'name' => $campaign['name'],
                'type' => $campaign['discount_type'],
                'value' => (float)$campaign['discount_value'],
                'apply_order' => $campaign['apply_order'],
                'matched_targets' => $campaign['matched_targets'] ?? [],
            ];

            $result->finalPrice = round(max($priceAfterCampaign, 0), 2);

            $result->trace->add('Campaña', $campaign['name'] . ' [' . $campaign['code'] . ']');
            $result->trace->add('Precio antes campaña', round($priceBeforeCampaign, 2));
            $result->trace->add('Precio campaña', $result->finalPrice);

            if ((int)$campaign['stop_processing'] === 1) {
                $result->trace->add('Campañas', 'STOP_PROCESSING');
                break;
            }

            if ((int)$campaign['stackable'] === 0) {
                $result->trace->add('Campañas', 'NO APILABLE');
                break;
            }
        }

        return $result;
    }

    private function applyDiscount(array $campaign, float $price): float
    {
        return match ($campaign['discount_type']) {
            'percent' => $price * (1 - ((float)$campaign['discount_value'] / 100)),
            'fixed_amount' => $price - (float)$campaign['discount_value'],
            'fixed_price' => (float)$campaign['discount_value'],
            default => $price,
        };
    }
}