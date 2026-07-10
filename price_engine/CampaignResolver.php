<?php
declare(strict_types=1);

class CampaignResolver
{
    public function resolve(
        array $campaigns,
        PriceResult $result
    ): PriceResult {
        $result->campaigns = $campaigns;

        return $result;
    }
}