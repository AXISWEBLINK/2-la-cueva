<?php
declare(strict_types=1);

class CampaignResolver
{
    public function resolve(
    array $campaigns,
    PriceResult $result
): PriceResult {

    foreach ($campaigns as $campaign) {

            $price = $result->finalPrice;

            switch ($campaign['discount_type']) {

                case 'percent':

                    $price = $price *
                        (1 - ((float)$campaign['discount_value'] / 100));

                    break;

                case 'fixed_amount':

                    $price = $price -
                        (float)$campaign['discount_value'];

                    break;

                case 'fixed_price':

                    $price =
                        (float)$campaign['discount_value'];

                    break;
            }

            $result->campaigns[] = [
                'id' => $campaign['id'],
                'code' => $campaign['code'],
                'name' => $campaign['name'],
                'type' => $campaign['discount_type'],
                'value' => $campaign['discount_value']
            ];

            $result->finalPrice = round($price, 2);

            $result->trace->add(
                'Campaña',
                $campaign['name']
            );

            $result->trace->add(
                'Precio campaña',
                $result->finalPrice
            );

            if ((int)$campaign['stop_processing'] === 1) {
                break;
            }

            if ((int)$campaign['stackable'] === 0) {
                break;
            }
        }

        return $result;
    }
}